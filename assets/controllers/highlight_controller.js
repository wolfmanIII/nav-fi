import { Controller } from '@hotwired/stimulus';
import hljs from 'highlight.js';

export default class extends Controller {
    connect() {
        this.element.querySelectorAll('code').forEach((block) => {
            hljs.highlightElement(block);
        });
    }
}
