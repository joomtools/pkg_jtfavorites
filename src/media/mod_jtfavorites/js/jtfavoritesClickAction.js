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
}(JtFavorites, document));

function modJtFavReady(fn) {
	if (document.readyState != 'loading') {
		fn();
	} else {
		document.addEventListener('DOMContentLoaded', fn);
	}
}

modJtFavReady(JtFavorites.changeListItemTask);

