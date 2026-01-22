import { Controller } from '@hotwired/stimulus';

/*
 * Questo è un esempio di controller Stimulus!
 *
 * Qualsiasi elemento con un attributo data-controller="hello" farà sì che
 * questo controller venga eseguito. Il nome "hello" viene dal filename:
 * hello_controller.js -> "hello"
 *
 * Elimina questo file o adattalo al tuo caso d'uso!
 */
export default class extends Controller {
    connect() {
        this.element.textContent = 'Hello Stimulus! Edit me in assets/controllers/hello_controller.js';
    }
}
