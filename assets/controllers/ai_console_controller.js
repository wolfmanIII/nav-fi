import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["messages", "input", "button"];

    connect() {
        // opzionale: log per debug
        // console.log("console controller connesso");
    }

    async submit(event) {
        event.preventDefault();

        const text = this.inputTarget.value.trim();
        if (!text) {
            return;
        }

        // Aggiungo il messaggio dell'utente alla chat
        this.appendMessage("user", text);
        this.inputTarget.value = "";

        this.toggleLoading(true);

        const assistantBubble = this.appendMessage("assistant", "");

        try {
            const streamedAnswer = await this.streamAnswer(text, assistantBubble);
            this.setBubbleText(assistantBubble, streamedAnswer);
        } catch (streamError) {
            this.setBubbleText(
                assistantBubble,
                "Errore durante la chiamata al chatbot (streaming)."
            );
            this.appendMessage(
                "error",
                "Streaming fallito: " +
                    (streamError.message || streamError.toString())
            );
        } finally {
            this.toggleLoading(false);
            this.scrollToBottom();
        }
    }

    async streamAnswer(question, bubbleElement) {
        const response = await fetch("/elara/api/chat/stream", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify({ question }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            const msg = errorData.error || `Errore HTTP ${response.status}`;
            throw new Error(msg);
        }

        if (!response.body || !response.body.getReader) {
            throw new Error("Streaming non supportato dal browser.");
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let fullText = "";
        let buffer = "";

        while (true) {
            const { value, done } = await reader.read();
            buffer += decoder.decode(value || new Uint8Array(), { stream: !done });

            let delimiterIndex;
            while ((delimiterIndex = buffer.indexOf("\n\n")) !== -1) {
                const eventChunk = buffer.slice(0, delimiterIndex);
                buffer = buffer.slice(delimiterIndex + 2);

                const dataLine = eventChunk
                    .split("\n")
                    .filter((line) => line.startsWith("data:"))
                    .map((line) => line.slice(5).trim())
                    .join("\n");

                if (!dataLine) {
                    continue;
                }

                let payload;
                try {
                    payload = JSON.parse(dataLine);
                } catch {
                    payload = { chunk: dataLine };
                }

                if (payload.chunk) {
                    fullText += payload.chunk;
                    this.setBubbleText(bubbleElement, fullText);
                    this.scrollToBottom();
                }

                if (payload.error) {
                    fullText = payload.error;
                    this.setBubbleText(bubbleElement, fullText);
                    return fullText;
                }

                if (payload.done) {
                    return fullText || "[Nessuna risposta dal engine Elara]";
                }
            }

            if (done) {
                break;
            }
        }

        // process any trailing buffer as plain text
        if (buffer.trim()) {
            fullText += buffer.trim();
            this.setBubbleText(bubbleElement, fullText);
        }

        return fullText || "[Nessuna risposta dal engine Elara]";
    }

    appendMessage(role, text) {
        const wrapper = document.createElement("div");

        let chatSideClass = "chat-start";
        let headerLabel = "Elara";
        let bubbleExtra = "";

        if (role === "user") {
            chatSideClass = "chat-end";
            headerLabel = "Tu";
        } else if (role === "error") {
            chatSideClass = "chat-start";
            headerLabel = "Errore";
            bubbleExtra = " chat-bubble-error";
        }

        wrapper.className = `chat ${chatSideClass}`;

        const header = document.createElement("div");
        header.className = "chat-header mb-1 text-xs opacity-70";
        header.textContent = headerLabel;

        const bubble = document.createElement("div");
        bubble.className = `chat-bubble${bubbleExtra} text-sm`;
        this.setBubbleText(bubble, text);

        wrapper.appendChild(header);
        wrapper.appendChild(bubble);

        this.messagesTarget.appendChild(wrapper);
        this.scrollToBottom();

        return bubble;
    }

    setBubbleText(bubbleElement, text) {
        if (!bubbleElement) {
            return;
        }
        bubbleElement.innerHTML = this.escapeHtml(text ?? "").replace(
            /\n/g,
            "<br>"
        );
    }


    toggleLoading(isLoading) {
        if (!this.hasButtonTarget) {
            return;
        }
        this.buttonTarget.disabled = isLoading;
        this.buttonTarget.classList.toggle("loading", isLoading);
    }

    scrollToBottom() {
        if (this.hasMessagesTarget) {
            this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight;
        }
    }

    escapeHtml(str) {
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}
