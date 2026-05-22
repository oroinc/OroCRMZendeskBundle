import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import BaseComponent from 'oroui/js/app/components/base/component';
import StandartConfirmation from 'oroui/js/standart-confirmation';
import messenger from 'oroui/js/messenger';
import mediator from 'oroui/js/mediator';
import routing from 'routing';

/**
 * Configuration constants for Zendesk OAuth integration
 * @private
 */
const OAUTH_CONFIG = {
    EVENT_TYPE: 'zendesk:oauth:done',
    STORAGE_EVENT_PREFIX: 'zendesk:oauth:done:',
    POPUP_NAME: 'ZendeskOAuth',
    POPUP_WIDTH: 800,
    POPUP_HEIGHT: 600,
    POPUP_FEATURES: ['resizable=yes', 'scrollbars=yes', 'status=yes']
};

const SELECTORS = {
    STATUS_BADGE: '#zendesk-oauth-badge-connection-status',
    BADGE_CONNECTED: '.zendesk-badge-connected',
    BADGE_NOT_CONNECTED: '.zendesk-badge-not-connected',
    TRANSPORT_ID_INPUT: 'input[name*="[id]"]',
    TRANSPORT_ID_ALT_INPUT: 'input[name*="transport"][name*="id"]'
};

const URL_PATTERNS = {
    TRANSPORT_ID: /\/(\d+)$/
};

const MESSAGE_TYPES = {
    ERROR: 'error'
};

const ROUTE_NAMES = {
    OAUTH_AUTHORIZE: 'oro_zendesk_oauth_authorize'
};

const TRANSLATION_KEYS = {
    CONFIRM_TITLE: 'oro.zendesk.oauth.auth_confirmation_popup.title',
    CONFIRM_MESSAGE: 'oro.zendesk.oauth.auth_confirmation_popup.message',
    CONFIRM_OK: 'oro.zendesk.oauth.auth_confirmation_popup.ok',
    CONFIRM_CANCEL: 'oro.zendesk.oauth.auth_confirmation_popup.cancel',
    MISSING_TRANSPORT: 'oro.zendesk.oauth.error.missing_transport',
    POPUP_BLOCKED: 'oro.zendesk.oauth.error.popup_blocked'
};

/**
 * Zendesk OAuth Component
 *
 * @extends BaseComponent
 */
const ZendeskOAuthComponent = BaseComponent.extend({
    /**
     * @inheritdoc
     * @param {Object} options - Component initialization options
     */
    constructor: function ZendeskOAuthComponent(options) {
        ZendeskOAuthComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     * @param {Object} options - Component initialization options
     */
    initialize(options) {
        BaseComponent.prototype.initialize.call(this, options);

        this._validateOptions(options);
        this.$button = options._sourceElement;
        this._boundHandlers = this._createBoundHandlers();
        this._attachEventListeners();
    },

    /**
     * Validate required initialization options
     * @param {Object} options - Initialization options
     * @throws {Error} If required options are missing
     * @private
     */
    _validateOptions(options) {
        if (!options._sourceElement) {
            throw new Error('ZendeskOAuthComponent requires _sourceElement option');
        }
    },

    /**
     * Create bound handler references for proper event cleanup
     * Following the principle of storing bound handlers to enable proper removal
     * @returns {Object} Object containing bound handler functions
     * @private
     */
    _createBoundHandlers() {
        return {
            onButtonClick: this._onButtonClick.bind(this),
            onMessage: this._onMessage.bind(this),
            onStorage: this._onStorage.bind(this)
        };
    },

    /**
     * Attach all event listeners
     * Centralizes event binding for clarity and maintainability
     * @private
     */
    _attachEventListeners() {
        this.$button.on('click', this._boundHandlers.onButtonClick);
        window.addEventListener('message', this._boundHandlers.onMessage);
        window.addEventListener('storage', this._boundHandlers.onStorage);
    },

    /**
     * Handle authorization button click event
     * @param {Event} event - Click event from button
     * @private
     */
    _onButtonClick(event) {
        event.preventDefault();
        this._confirmOAuthFlow();
    },

    /**
     * Show confirmation modal before opening OAuth popup
     * @private
     */
    _confirmOAuthFlow() {
        const confirm = new StandartConfirmation({
            title: __(TRANSLATION_KEYS.CONFIRM_TITLE),
            content: __(TRANSLATION_KEYS.CONFIRM_MESSAGE),
            okText: __(TRANSLATION_KEYS.CONFIRM_OK),
            cancelText: __(TRANSLATION_KEYS.CONFIRM_CANCEL)
        });

        confirm.on('ok', this._initiateOAuthFlow.bind(this))
            .open();
    },

    /**
     * Handle incoming postMessage events from OAuth popup
     * @param {MessageEvent} event - Message event from popup window
     * @private
     */
    _onMessage(event) {
        if (!this._isValidOAuthMessage(event)) {
            return;
        }

        this._processOAuthResult(event.data);
    },

    /**
     * Handle OAuth completion fallback via localStorage events
     * @param {StorageEvent} event - Storage event emitted by popup callback page
     * @private
     */
    _onStorage(event) {
        if (!event.key || !event.key.startsWith(OAUTH_CONFIG.STORAGE_EVENT_PREFIX) || !event.newValue) {
            return;
        }

        localStorage.removeItem(event.key);

        const data = this._parseStoragePayload(event.newValue);
        if (!this._hasValidMessageStructure(data)) {
            return;
        }

        this._processOAuthResult(data);
    },

    /**
     * Parse OAuth payload from storage event safely
     * @param {string} payload - Serialized payload from localStorage
     * @returns {Object|null} Parsed object or null on parse error
     * @private
     */
    _parseStoragePayload(payload) {
        try {
            return JSON.parse(payload);
        } catch (error) {
            return null;
        }
    },

    /**
     * Initiate the OAuth authorization flow
     * Orchestrates the complete OAuth initiation process
     * @private
     */
    _initiateOAuthFlow() {
        const transportId = this._resolveTransportId();

        if (!this._validateTransportId(transportId)) {
            return;
        }

        const authorizationUrl = routing.generate(ROUTE_NAMES.OAUTH_AUTHORIZE, {id: transportId});
        this._launchOAuthPopup(authorizationUrl);
    },

    /**
     * Resolve transport ID from form or URL
     * Implements fallback strategy for transport ID resolution
     * @returns {string|null} Transport ID or null if not found
     * @private
     */
    _resolveTransportId() {
        const $form = this.$button.closest('form');

        return this._extractTransportIdFromForm($form) ||
            this._extractTransportIdFromUrl();
    },

    /**
     * Extract transport ID from form fields
     * @param {jQuery} $form - Form element containing transport ID
     * @returns {string|null} Transport ID from form inputs
     * @private
     */
    _extractTransportIdFromForm($form) {
        if (!$form.length) {
            return null;
        }

        return $form.find(SELECTORS.TRANSPORT_ID_INPUT).val() ||
            $form.find(SELECTORS.TRANSPORT_ID_ALT_INPUT).val() ||
            null;
    },

    /**
     * Extract transport ID from current URL path
     * @returns {string|null} Transport ID from URL pattern match
     * @private
     */
    _extractTransportIdFromUrl() {
        const match = window.location.pathname.match(URL_PATTERNS.TRANSPORT_ID);
        return match ? match[1] : null;
    },

    /**
     * Validate transport ID and show error if invalid
     * @param {string|null} transportId - Transport ID to validate
     * @returns {boolean} True if valid, false otherwise
     * @private
     */
    _validateTransportId(transportId) {
        if (!transportId) {
            this._showError(TRANSLATION_KEYS.MISSING_TRANSPORT);
            return false;
        }

        return true;
    },

    /**
     * Launch OAuth popup window with calculated positioning
     * @param {string} url - Authorization URL to open
     * @private
     */
    _launchOAuthPopup(url) {
        const windowFeatures = this._buildPopupWindowFeatures();
        const popup = window.open(url, OAUTH_CONFIG.POPUP_NAME, windowFeatures);

        if (this._isPopupBlocked(popup)) {
            this._showError(TRANSLATION_KEYS.POPUP_BLOCKED);
            return;
        }

        popup.focus();
    },

    /**
     * Build popup window features string with centered positioning
     * @returns {string} Comma-separated window features
     * @private
     */
    _buildPopupWindowFeatures() {
        const position = this._calculateCenteredPosition();
        const dimensionFeatures = [
            `width=${OAUTH_CONFIG.POPUP_WIDTH}`,
            `height=${OAUTH_CONFIG.POPUP_HEIGHT}`,
            `left=${position.left}`,
            `top=${position.top}`
        ];

        return [...dimensionFeatures, ...OAUTH_CONFIG.POPUP_FEATURES].join(',');
    },

    /**
     * Calculate centered position for popup window
     * @returns {Object} Object with left and top coordinates
     * @private
     */
    _calculateCenteredPosition() {
        const screenX = window.screenX || screen.left;
        const screenY = window.screenY || screen.top;

        return {
            left: screenX + (window.outerWidth - OAUTH_CONFIG.POPUP_WIDTH) / 2,
            top: screenY + (window.outerHeight - OAUTH_CONFIG.POPUP_HEIGHT) / 2
        };
    },

    /**
     * Check if popup was blocked by browser
     * @param {Window|null} popup - Popup window reference
     * @returns {boolean} True if popup was blocked
     * @private
     */
    _isPopupBlocked(popup) {
        return !popup || popup.closed || typeof popup.closed === 'undefined';
    },

    /**
     * Process OAuth result received from popup
     * @param {Object} data - OAuth result data
     * @param {boolean} data.success - Whether authorization succeeded
     * @param {string} data.messageType - Type of message to display
     * @param {string} data.message - Message content
     * @private
     */
    _processOAuthResult(data) {
        if (data.success) {
            this._updateAuthorizationStatus();
        }

        this._showMessage(data.messageType, data.message);
    },

    /**
     * Update UI to reflect successful authorization
     * Implements fallback to page reload if badge container is unavailable
     * @private
     */
    _updateAuthorizationStatus() {
        const $statusContainer = $(SELECTORS.STATUS_BADGE);

        if (!$statusContainer.length) {
            this._reloadPageGracefully();
            return;
        }

        this._toggleAuthorizationBadges($statusContainer);
    },

    /**
     * Toggle authorization badge visibility
     * @param {jQuery} $container - Status badge container element
     * @private
     */
    _toggleAuthorizationBadges($container) {
        $container.find(SELECTORS.BADGE_CONNECTED).show();
        $container.find(SELECTORS.BADGE_NOT_CONNECTED).hide();
    },

    /**
     * Reload page with loading indicator
     * @private
     */
    _reloadPageGracefully() {
        mediator.execute('showLoading');
        window.location.reload();
    },

    /**
     * Validate incoming OAuth message for security and correctness
     * Ensures message origin matches current origin (CSRF protection)
     * @param {MessageEvent} event - Message event to validate
     * @returns {boolean} True if message is valid and safe to process
     * @private
     */
    _isValidOAuthMessage(event) {
        const data = event.data;

        if (!this._hasValidMessageStructure(data)) {
            return false;
        }

        return true;
    },

    /**
     * Check if message has valid structure
     * @param {*} data - Message data to validate
     * @returns {boolean} True if structure is valid
     * @private
     */
    _hasValidMessageStructure(data) {
        return data && data.type === OAUTH_CONFIG.EVENT_TYPE;
    },

    /**
     * Display error notification to user
     * @param {string} translationKey - Translation key for error message
     * @private
     */
    _showError(translationKey) {
        this._showMessage(MESSAGE_TYPES.ERROR, __(translationKey));
    },

    /**
     * Display notification message to user
     * @param {string} type - Message type (error, success, etc.)
     * @param {string} message - Message content
     * @private
     */
    _showMessage(type, message) {
        messenger.notificationFlashMessage(type, message);
    },

    /**
     * Clean up component resources
     * Removes event listeners to prevent memory leaks
     * @inheritdoc
     */
    dispose() {
        if (this.disposed) {
            return;
        }

        this._detachEventListeners();
        BaseComponent.prototype.dispose.call(this);
    },

    /**
     * Detach all event listeners
     * Centralizes event cleanup for maintainability
     * @private
     */
    _detachEventListeners() {
        if (this.$button) {
            this.$button.off('click', this._boundHandlers.onButtonClick);
        }

        if (this.$authorizationType && this.$authorizationType.length) {
            this.$authorizationType.off('change', this._boundHandlers.onAuthorizationTypeChange);
        }

        window.removeEventListener('message', this._boundHandlers.onMessage);
        window.removeEventListener('storage', this._boundHandlers.onStorage);
    }
});

export default ZendeskOAuthComponent;
