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

        try {
            const response = await fetch("/elara/api/chat", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({ message: text }),
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                const msg = errorData.error || `Errore HTTP ${response.status}`;
                throw new Error(msg);
            }

            const data = await response.json();
            const answer = data.answer ?? "[Nessuna risposta dal engine Elara]";

            this.appendMessage("assistant", answer);
        } catch (e) {
            this.appendMessage(
                "error",
                "Errore durante la chiamata al chatbot: " + (e.message || e.toString())
            );
        } finally {
            this.toggleLoading(false);
            this.scrollToBottom();
        }
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
        wrapper.innerHTML = `
            <div class="chat-header mb-1 text-xs opacity-70">
                ${this.escapeHtml(headerLabel)}
            </div>
            <div class="chat-bubble${bubbleExtra} text-sm">
                ${this.escapeHtml(text).replace(/\n/g, "<br>")}<br>
            </div>
        `;

        this.messagesTarget.appendChild(wrapper);
        this.scrollToBottom();
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
