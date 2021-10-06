/**
 * Environment config retrieval class.
 * 
 * @type {{retrieveConfig: ((function(): Promise<Object|boolean|*>)|*), workerSuffix: string, useDummyData: boolean, defaultValue: null, get: ((function(*=): Promise<*|null>)|*), dummyData: (function(): {sb_acd_worker_3pp_proxy: boolean, sb_acd_image_resize: boolean, sb_acd_worker_google_fonts: boolean}), getSiteUrl: (function(): *), getConfigUrl: (function())}}
 */
window.envConfig = {

    /**
     * The default value if the property is not set in the environment configuration object.
     */
    defaultValue: null,

    /**
     * Whether to use dummy data or not.
     */
    useDummyData: false,

    /**
     * The suffix to be added to the site URL to retrieve the environment configuration.
     */
    workerSuffix: '/acd-cgi/config',

    /**
     * Get an item from the environment configuration object.
     *
     * @param key
     * @returns {Promise<null|*>}
     */
    get: async function (key) {
        var configObject = await this.retrieveConfig();
        if (!configObject || typeof configObject !== 'object') {
            return null; // Could not retrieve config
        }
        if (!configObject.hasOwnProperty(key)) {
            return this.defaultValue; // Property not set
        }
        return configObject[key];
    },

    /**
     * Get site URL.
     *
     * @returns {Promise<*>}
     */
    getSiteUrl: async function () {
        return sb_ajax_object.site_url;
    },

    /**
     * Get the URL to retrieve the environment configuration.
     * @returns {Promise<string>}
     */
    getConfigUrl: async function () {
        return await this.getSiteUrl() + this.workerSuffix;
    },

    /**
     * Retrieve the environment configuration.
     *
     * @returns {Promise<object|boolean|any>}
     */
    retrieveConfig: async function () {
        if (this.useDummyData) {
            return await this.dummyData();
        }
        try {
            const response = await fetch(await this.getConfigUrl());
            const configObject = await response.json();
            return configObject;
        } catch (error) {
            //console.error(error);
            return false;
        }
    },

    /**
     * Dummy data.
     *
     * @returns {Promise<{sb_acd_worker_3pp_proxy: boolean, sb_acd_image_resize: boolean, sb_acd_worker_google_fonts: boolean}>}
     */
    dummyData: async function () {
        return {
            sb_acd_image_resize: true,
            sb_acd_worker_google_fonts: true,
            sb_acd_worker_3pp_proxy: true
        };
    }
};
