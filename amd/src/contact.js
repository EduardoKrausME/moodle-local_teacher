/**
 * Show the teacher contact form in Moodle's native modal.
 *
 * @module local_teacher/contact
 * @copyright 2026 Eduardo Kraus
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(["jquery", "core/modal", "core/notification"], function($, Modal, Notification) {
    "use strict";

    const init = function() {
        const trigger = document.querySelector("[data-action='local-teacher-contact-open']");
        const content = document.getElementById("local-teacher-contact-template");
        if (!trigger || !content) {
            return;
        }

        let modalPromise = null;
        trigger.addEventListener("click", function() {
            if (!modalPromise) {
                modalPromise = Modal.create({
                    title: trigger.textContent.trim(),
                    body: content.innerHTML,
                    returnElement: trigger,
                    isVerticallyCentered: true,
                    removeOnClose: false,
                }).then(function(modal) {
                    const modalElement = modal.getRoot()[0];
                    modalElement.classList.add("local-teacher-contact-modal");
                    const cancel = modalElement.querySelector("[data-action='local-teacher-contact-cancel']");
                    if (cancel) {
                        cancel.addEventListener("click", function() {
                            modal.hide();
                        });
                    }
                    return modal;
                });
            }

            modalPromise.then(function(modal) {
                return $.when(modal.show()).then(function() {
                    const message = modal.getRoot()[0].querySelector("textarea[name='message']");
                    if (message) {
                        message.focus();
                    }
                    return;
                });
            }).catch(function(error) {
                modalPromise = null;
                Notification.exception(error);
            });
        });
    };

    return {init: init};
});
