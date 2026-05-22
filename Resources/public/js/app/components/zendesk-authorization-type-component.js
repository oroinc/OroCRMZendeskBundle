import $ from 'jquery';
import BaseComponent from 'oroui/js/app/components/base/component';

const SELECTORS = {
    AUTHORIZATION_TYPE: 'select.authorization-type',
    AUTH_OAUTH: '.auth.oauth',
    AUTH_EMAIL_TOKEN: '.auth.email-token',
    CONNECT_BUTTON: '.zendesk-connect-button',
    STATUS_BADGE: '#zendesk-oauth-badge-connection-status'
};

const AUTHORIZATION_TYPE = {
    OAUTH: 'oauth'
};

/**
 * Toggles Zendesk auth-related form rows based on selected authorization type.
 */
const ZendeskAuthorizationTypeComponent = BaseComponent.extend({
    /**
     * @inheritdoc
     */
    constructor: function ZendeskAuthorizationTypeComponent(options) {
        ZendeskAuthorizationTypeComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     * @param {Object} options
     */
    initialize(options) {
        BaseComponent.prototype.initialize.call(this, options);

        const $sourceElement = options._sourceElement;

        this.$form = $sourceElement.is('form') ? $sourceElement : $sourceElement.closest('form');
        this.$authorizationType = this.$form.find(SELECTORS.AUTHORIZATION_TYPE).first();

        if (!this.$authorizationType.length || !this.$form.length) {
            return;
        }

        this._syncAuthorizationTypeFields = this._syncAuthorizationTypeFields.bind(this);
        this.$authorizationType.on('change', this._syncAuthorizationTypeFields);

        this._syncAuthorizationTypeFields();
    },

    /**
     * Shows/hides auth-specific fields based on the current authorization type value.
     *
     * @private
     */
    _syncAuthorizationTypeFields() {
        const isOAuth = this.$authorizationType.val() === AUTHORIZATION_TYPE.OAUTH;

        this._setVisibility(this.$form.find(SELECTORS.AUTH_OAUTH), isOAuth);
        this._setVisibility(this.$form.find(SELECTORS.AUTH_EMAIL_TOKEN), !isOAuth);
        this._setVisibility(this.$form.find(SELECTORS.CONNECT_BUTTON), isOAuth);
        this._setVisibility($(SELECTORS.STATUS_BADGE), isOAuth);
    },

    /**
     * Toggles visibility of a jQuery element set.
     *
     * @param {jQuery} $el
     * @param {boolean} isVisible
     * @private
     */
    _setVisibility($el, isVisible) {
        $el.toggleClass('hide', !isVisible).toggle(isVisible);
    },

    /**
     * @inheritdoc
     */
    dispose() {
        if (this.disposed) {
            return;
        }

        if (this.$authorizationType) {
            this.$authorizationType.off('change', this._syncAuthorizationTypeFields);
        }

        ZendeskAuthorizationTypeComponent.__super__.dispose.call(this);
    }
});

export default ZendeskAuthorizationTypeComponent;
