/**
 * Floating Kanban summary button.
 *
 * @module     block_buttonkb/button
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = (widgetId) => {
    const widget = document.getElementById(widgetId);
    if (!widget || widget.dataset.initialised === 'true') {
        return;
    }
    widget.dataset.initialised = 'true';

    const block = widget.closest('.block_buttonkb');
    document.body.appendChild(widget);
    if (block) {
        block.hidden = true;
    }

    const trigger = widget.querySelector('.buttonkb-trigger');
    const panel = widget.querySelector('.buttonkb-panel');
    const close = widget.querySelector('.buttonkb-close');
    if (!trigger || !panel || !close) {
        throw new Error('Kanban quick-view controls are missing.');
    }

    const setOpen = (open, restoreFocus = true) => {
        panel.hidden = !open;
        trigger.setAttribute('aria-expanded', String(open));
        if (open) {
            close.focus();
        } else if (restoreFocus) {
            trigger.focus();
        }
    };

    trigger.addEventListener('click', () => {
        setOpen(panel.hidden);
    });
    close.addEventListener('click', () => {
        setOpen(false);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            setOpen(false);
        }
    });
    document.addEventListener('click', (event) => {
        if (!panel.hidden && !widget.contains(event.target)) {
            setOpen(false, false);
        }
    });
};
