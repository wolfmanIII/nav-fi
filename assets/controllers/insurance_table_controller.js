import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['hidden', 'radio'];

    select(event) {
        const clicked = event.currentTarget;
        const value   = clicked.dataset.value;

        this.hiddenTarget.value = value;
    }
}