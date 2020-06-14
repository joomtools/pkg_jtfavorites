/**
 * @package      Joomla.Administrator
 * @subpackage   mod_jtfavorites
 *
 * @author       Guido De Gobbis <support@joomtools.de>
 * @copyright    2020 JoomTools.de - All rights reserved.
 * @license      GNU General Public License version 3 or later
 */

var JtFavorites = window.JtFavorites || {};

(function (JtFavorites, document) {
	"use strict";

	/**
	 * Ajax send form
	 *
	 * @param   {node}  form  The form element
	 *
	 * @returns   {void}
	 */
	JtFavorites.sendData = function (form) {
		var XHR = new XMLHttpRequest();

		// Bind the FormData object and the form element
		var FD = new FormData(form);

		// Define what happens on successful data submission
		XHR.addEventListener("load", function (event) {
			location.reload();
		});

		// Define what happens in case of error
		XHR.addEventListener("error", function (event) {
			console.error('mod_jtfavorites: Oops! Something went wrong on sending form data.');
		});

		// Set up our request
		XHR.open("POST", form.getAttribute("action"));

		// The data sent is what the user provided in the form
		XHR.send(FD);
	};

	/**
	 * Generic submit form
	 *
	 * @param   {String}  task      The given task
	 * @param   {node}    form      The form element
	 * @param   {bool}    validate  The form element
	 *
	 * @returns   {void}
	 */
	JtFavorites.submitform = function (task, form, validate) {
		// ...and take over its submit event.
		form.addEventListener("submit", function (event) {
			event.preventDefault();

			JtFavorites.sendData(form);
		});

		if (task) {
			form.task.value = task;
		}

		// Toggle HTML5 validation
		form.noValidate = !validate;

		if (!validate) {
			form.setAttribute('novalidate', '');
		} else if (form.hasAttribute('novalidate')) {
			form.removeAttribute('novalidate');
		}

		// Submit the form.
		// Create the input type="submit"
		var button = document.createElement('input');
		button.style.display = 'none';
		button.type = 'submit';

		// Append it and click it
		form.appendChild(button).click();

		// If "submit" was prevented, make sure we don't get a build up of buttons
		form.removeChild(button);
	};

	JtFavorites.findAncestorForm = function (string) {
		var formId = string.match(/^[a-zA-Z]+/);

		if (formId === null || typeof formId === 'undefined') return false;

		return document.querySelector('#' + formId.toString());
	};

	/**
	 * USED IN: all over :)
	 *
	 * @param   {string}  id    The id
	 * @param   {string}  task  The task
	 *
	 * @return   {boolean}
	 */
	JtFavorites.listItemTask = function (id, task) {
		var f = JtFavorites.findAncestorForm(id),
			i = 0, cbx,
			cb = f[id];

		if (!cb) return false;

		while (true) {
			cbx = f['cb' + i];

			if (!cbx) break;

			cbx.checked = false;

			i++;
		}

		cb.checked = true;
		f.boxchecked.value = 1;

		var extension = task.split(".", 1);

		f.setAttribute("action", f.getAttribute('data-' + extension + '-action'));

		JtFavorites.submitform(task, f);
		return false;
	};

	/**
	 * Exchange the call
	 * Must be adapted if the call 'listItemTask' is changed in Joomla
	 */
	JtFavorites.changeListItemTask = function () {
		var items = document.querySelectorAll('.mod_jtfavorites *[onclick*=listItemTask]');
		Array.prototype.forEach.call(items, function (elm) {
			var onclick = elm.getAttribute('onclick');
			elm.setAttribute('onclick', onclick.replace('listItemTask', 'JtFavorites.listItemTask'));
		});
	};

	/**
	 * Add CSRF-Token on custom actions and return message on success
	 */
	JtFavorites.customActionsAddGetParams = function () {
		var items = document.querySelectorAll('.mod_jtfavorites .core .ext-link'),
			checkinIcon = document.querySelectorAll('.mod_jtfavorites .click-action a[onclick*=checkin]'),
			token = window.Joomla.getOptions('csrf.token', ''),
			processIconCss = document.createElement('style'),
			processIcon = document.createElement('span'),
			errorMessage = document.createElement('span'),
			successIcon = document.createElement('span');

		processIconCss.setAttribute("type", "text/css");
		processIconCss.appendChild(document.createTextNode(".spinner {\n" +
			"  display: inline-block;\n" +
			"  height: 14px;\n" +
			"  vertical-align: middle;\n" +
			"  line-height: 18px;\n" +
			"  margin-right: 4px;\n" +
			"}\n" +
			".spinner > span {\n" +
			"  background-color: #333;\n" +
			"  margin-left: 2px;\n" +
			"  height: 100%;\n" +
			"  width: 3px;\n" +
			"  display: inline-block;\n" +
			"  -webkit-animation: sk-stretchdelay 1.2s infinite ease-in-out;\n" +
			"  animation: sk-stretchdelay 1.2s infinite ease-in-out;\n" +
			"}\n" +
			".spinner .rect2 {\n" +
			"  -webkit-animation-delay: -1.1s;\n" +
			"  animation-delay: -1.1s;\n" +
			"}\n" +
			".spinner .rect3 {\n" +
			"  -webkit-animation-delay: -1.0s;\n" +
			"  animation-delay: -1.0s;\n" +
			"}\n" +
			"@-webkit-keyframes sk-stretchdelay {\n" +
			"  0%, 40%, 100% { -webkit-transform: scaleY(0.4) }  \n" +
			"  20% { -webkit-transform: scaleY(1.0) }\n" +
			"}\n" +
			"@keyframes sk-stretchdelay {\n" +
			"  0%, 40%, 100% { \n" +
			"    transform: scaleY(0.4);\n" +
			"    -webkit-transform: scaleY(0.4);\n" +
			"  }  20% { \n" +
			"    transform: scaleY(1.0);\n" +
			"    -webkit-transform: scaleY(1.0);\n" +
			"  }\n" +
			"}"));
		document.head.appendChild(processIconCss);

		processIcon.setAttribute('class', 'spinner');
		processIcon.setAttribute('aria-hidden', 'true');
		processIcon.innerHTML = '<span class="rect1"></span><span class="rect2"></span><span class="rect3"></span>';

		successIcon.setAttribute('class', 'icon-save');
		successIcon.setAttribute('aria-hidden', 'true');

		errorMessage.setAttribute('class', 'error');
		errorMessage.setAttribute('aria-hidden', 'true');

		Array.prototype.forEach.call(items, function (elm) {
			var href = elm.getAttribute('href');

			elm.addEventListener('click', function (event) {
				event.stopPropagation();
				event.preventDefault();

				//elm.parentNode.appendChild(processIcon);
				elm.parentNode.prepend(processIcon);

				window.Joomla.request({
					url: href,
					headers: {
						'X-CSRF-Token': token
					},
					onError: function (xhr) {
						console.error('ERROR: ', xhr);
					},
					onSuccess: function (response) {
						var icon;

						try {
							response = JSON.parse(response);
						} catch (e) {
							response = {succsess: true};
						}

						elm.parentNode.removeChild(processIcon);

						if (response.success === true) {
							elm.parentNode.prepend(successIcon);
							icon = successIcon;
						}

						if (response.success === false) {
							console.error('ERROR: ', response.message);
							errorMessage.innerHTML = '<span style="color:red;">' + response.message + '</span>';
							elm.parentNode.appendChild(errorMessage);
							icon = errorMessage;
						}

						Array.prototype.forEach.call(checkinIcon, function (el) {
							el.parentNode.removeChild(el);
						});

						setTimeout(function () {
							elm.parentNode.removeChild(icon);
						}, 4000);
					}
				});
			});
		});
	};
}(JtFavorites, document));

function modJtFavReady(fn) {
	if (document.readyState != 'loading') {
		fn();
	} else {
		document.addEventListener('DOMContentLoaded', fn);
	}
}

modJtFavReady(JtFavorites.changeListItemTask);
modJtFavReady(JtFavorites.customActionsAddGetParams);

