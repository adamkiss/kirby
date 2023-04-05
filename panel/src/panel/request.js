import { buildUrl, isSameOrigin, makeAbsolute } from "@/helpers/url.js";
import { toLowerKeys } from "../helpers/object.js";
import JsonRequestError from "@/errors/JsonRequestError.js";
import RequestError from "@/errors/RequestError.js";

/**
 * Creates a proper request body
 *
 * @param {String|FormData|Object|Array}
 * @returns {String}
 */
export const body = (body) => {
	if (body instanceof HTMLFormElement) {
		body = new FormData(body);
	}

	if (body instanceof FormData) {
		body = Object.fromEntries(body);
	}

	if (typeof body === "object") {
		return JSON.stringify(body);
	}

	return body;
};

/**
 * Convert globals to comma separated string
 * @param {Array|String} globals
 * @returns {String|false}
 */
export const globals = (globals) => {
	if (globals) {
		if (Array.isArray(globals) === false) {
			return String(globals);
		}

		return globals.join(",");
	}

	return false;
};

/**
 * Builds all required headers for a request
 *
 * @param {Object} headers
 * @param {Object} options All request options
 * @returns {Object}
 */
export const headers = (headers = {}, options = {}) => {
	return {
		"content-type": "application/json",
		"x-csrf": options.csrf ?? false,
		"x-fiber": true,
		"x-fiber-globals": globals(options.globals),
		"x-fiber-referrer": options.referrer ?? false,
		...toLowerKeys(headers)
	};
};

/**
 * @param {string|URL} url
 * @returns false
 */
export const redirect = (url) => {
	window.location.href = makeAbsolute(url);
	return false;
};

/**
 * Sends a Panel request to the backend with
 * all the right headers and other options.
 *
 * It also makes sure to redirect requests,
 * which cannot be handled via fetch and
 * throws more useful errors.
 *
 * @param {String} url
 * @param {Object} options
 * @returns {Object} {request, response}
 */
export const request = async (url, options = {}) => {
	// merge with a few defaults
	options = {
		cache: "no-store",
		credentials: "same-origin",
		mode: "same-origin",
		...options
	};

	// those need a bit more work
	options.body = body(options.body);
	options.headers = headers(options.headers, options);
	options.url = buildUrl(url, options.query);

	// The request object is a nice way to access all the
	// important parts later in errors for example
	const request = new Request(options.url, options);

	// Don't even try to request a
	// cross-origin url. Redirect instead.
	if (isSameOrigin(request.url) === false) {
		return redirect(request.url);
	}

	const response = await fetch(request);

	// redirect to non-fiber requests
	if (
		response.headers.get("Content-Type").includes("application/json") === false
	) {
		return redirect(response.url);
	}

	// try to parse the response.
	try {
		response.text = await response.text();
		response.json = JSON.parse(response.text);
	} catch (error) {
		throw new JsonRequestError("Invalid JSON response", {
			cause: error,
			request,
			response
		});
	}

	if (response.ok === false) {
		throw new RequestError(`The request to ${response.url} failed`, {
			request,
			response
		});
	}

	return {
		request,
		response
	};
};

export default request;