/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_OrderAttributes
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

define([
    'ko',
    'underscore',
    'jquery',
    'mage/translate'
], function (ko, _, $, $t) {
    'use strict';

    var defaults = {
        dateFormat: 'mm\/dd\/yyyy',
        showsTime: false,
        timeFormat: null,
        buttonImage: null,
        buttonImageOnly: null,
        buttonText: $t('Select Date')
    };
    var mpDateFormat, mpTimeFormat;

    ko.bindingHandlers.datepicker = {
        /**
         * Initializes calendar widget on element and stores it's value to observable property.
         * Datepicker binding takes either observable property or object
         *  { storage: {ko.observable}, options: {Object} }.
         * For more info about options take a look at "mage/calendar" and jquery.ui.datepicker widget.
         * @param {HTMLElement} el - Element, that binding is applied to
         * @param {Function} valueAccessor - Function that returns value, passed to binding
         */
        init: function (el, valueAccessor) {
            var config = valueAccessor(),
                observable,
                options = {};

            _.extend(options, defaults);

            if (typeof config === 'object') {
                observable = config.storage;
                _.extend(options, config.options);
            } else {
                observable = config;
            }

            require(['mage/calendar'], function () {
                $(el).calendar(options);

                ko.utils.registerEventHandler(el, 'change', function () {
                    observable(this.value);
                });
            });
        },

        /**
         * Update calendar widget on element and stores it's value to observable property.
         * Datepicker binding takes either observable property or object
         *  { storage: {ko.observable}, options: {Object} }.
         * @param {HTMLElement} element - Element, that binding is applied to
         * @param {Function} valueAccessor - Function that returns value, passed to binding
         */
        update: function (element, valueAccessor) {
            var config = valueAccessor(),
                $element = $(element),
                observable,
                options = {},
                newVal;

            _.extend(options, defaults);

            if (typeof config === 'object') {
                observable = config.storage;
                _.extend(options, config.options);
            } else {
                observable = config;
            }

            require(['moment', 'mage/utils/misc', 'Mageplaza_OrderAttributes/js/model/order-attributes-data', 'mage/calendar'], function (moment, utils, oaData) {
                var dateFormat = options.mpDateFormat ? options.mpDateFormat.replace(/mm|m/g,'MM') : options.dateFormat,
                    timeFormat = options.mpTimeFormat ? options.mpTimeFormat.replace(/TT|T/g,'aa') : options.timeFormat;
                mpDateFormat   = mpDateFormat || oaData.getData('mpDateFormat');
                mpTimeFormat   = mpTimeFormat || oaData.getData('mpTimeFormat');
                if (options.mpDateFormat) {
                    oaData.setData('mpDateFormat',options.mpDateFormat.replace(/mm|m/g,'MM'));
                    if (mpDateFormat) {
                        dateFormat = mpDateFormat;
                    }
                }
                if (options.mpTimeFormat) {
                    oaData.setData('mpTimeFormat',options.mpTimeFormat.replace(/TT|T/g,'aa'));
                    if (mpTimeFormat) {
                        timeFormat = mpTimeFormat;
                    }
                }
                if (_.isEmpty(observable())) {
                    newVal = null;
                } else {
                    newVal = moment(
                        observable(),
                        utils.convertToMomentFormat(
                            dateFormat + (options.showsTime ? ' ' + timeFormat : '')
                        )
                    ).toDate();
                }

                if (!options.timeOnly) {
                    $element.datepicker('setDate', newVal);
                    $element.blur();
                } else {
                    var tp_inst = $.datepicker._get($.datepicker._getInst($element[0]), 'timepicker');
                    if (tp_inst && observable()) {
                        var time = observable().split(' ')[0],
                            ampm = observable().split(' ')[1];

                        tp_inst.hour = time.split(':')[0] ? parseInt(time.split(':')[0], 10) : 0;
                        if (['PM','pm'].indexOf(ampm) > -1 && tp_inst.hour !== 12) {
                            tp_inst.hour += 12;
                        }
                        if (['AM','am'].indexOf(ampm) > -1 && tp_inst.hour === 12) {
                            tp_inst.hour = 0;
                        }
                        tp_inst.minute = time.split(':')[1] ? parseInt(time.split(':')[1], 10) : 0;
                        tp_inst.second = time.split(':')[2] ? parseInt(time.split(':')[2], 10) : 0;

                        time = $.datepicker.formatTime(options.timeFormat, tp_inst, {});
                        $element.val(time);
                        $element.trigger('change');
                    }
                }
            });
        }
    };
});
