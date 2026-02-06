/**
 * Pre-order Date Picker - Basic Slot and Fill Implementation
 * 
 * Following WooCommerce official documentation for ExperimentalOrderMeta
 * Reference: https://github.com/woocommerce/woocommerce/blob/trunk/docs/block-development/extensible-blocks/cart-and-checkout-blocks/available-slot-fills.md
 */

(function() {
    'use strict';
    
    // Wait for WordPress plugins API and WooCommerce Blocks to be available
    if (typeof window.wp !== 'undefined' && 
        window.wp.plugins && 
        window.wp.element && 
        window.wp.data && 
        window.wp.i18n && 
        window.wc && 
        window.wc.blocksCheckout) {
        
        const { __ } = window.wp.i18n;
        const { registerPlugin } = window.wp.plugins;
        const { ExperimentalOrderMeta } = window.wc.blocksCheckout;
        const { createElement: el, useState, useEffect, useRef } = window.wp.element;
        const { useSelect, useDispatch } = window.wp.data;
        
        /**
         * Create Pre-order Date Picker Component.
         */
        const PreOrderDatePickerComponent = () => {
            // Get default date from backend
            const defaultDate = window.preOrderDatePicker?.defaultDate || new Date().toISOString().split('T')[0];
            const [selectedDate, setSelectedDate] = useState(defaultDate);
            const inputRef = useRef(null);
            
            // Get dispatch functions for updating checkout store
            const { setExtensionData } = useDispatch('wc/store/checkout');
            
            // Send default date to backend on component mount
            useEffect(() => {
                setExtensionData('preorder-date-picker', {
                    pre_order_date: defaultDate
                });
            }, [defaultDate, setExtensionData]);
            
            // Initialize jQuery UI DatePicker
            useEffect(() => {
                if (inputRef.current && window.jQuery && window.jQuery.ui) {
                    console.log('Initializing datepicker with date:', defaultDate);
                    
                    // Initialize jQuery UI DatePicker
                    window.jQuery(inputRef.current).datepicker({
                        dateFormat: 'yy-mm-dd',
                        minDate: defaultDate,
                        defaultDate: defaultDate,
                        changeMonth: true,
                        changeYear: true,
                        yearRange: 'c:c+2',
                        onSelect: function(dateText) {
                            setSelectedDate(dateText);
                            
                            // Store in extension data for Store API compatibility
                            setExtensionData('preorder-date-picker', {
                                pre_order_date: dateText
                            });
                            
                            // Also try alternative approach - store directly in extensions
                            setExtensionData('woocommerce-blocks', {
                                preorder_date: dateText
                            });
                        }
                    });
                    
                    // Set initial value
                    window.jQuery(inputRef.current).datepicker('setDate', defaultDate);
                }
            }, [defaultDate, setExtensionData]);
            
            // Handle date change (fallback for manual input)
            const handleDateChange = (event) => {
                const date = event.target.value;
                setSelectedDate(date);
                
                // Store in extension data for Store API compatibility
                setExtensionData('preorder-date-picker', {
                    pre_order_date: date
                });
                
                // Also try alternative approach - store directly in extensions
                setExtensionData('woocommerce-blocks', {
                    preorder_date: date
                });
            };
            
            // Get translations from localized script
            const translations = window.preOrderDatePicker?.translations || {
                preOrderDateLabel: 'Preorder Date',
                preOrderDatePlaceholder: 'Select a date'
            };
            
            return el('div', {
                className: 'wc-block-components-text-input preorder-date-picker-container'
            }, [
                // Label
                el('label', {
                    key: 'label',
                    htmlFor: 'preorder-date-input',
                    className: 'wc-block-components-text-input__label'
                }, translations.preOrderDateLabel),
                
                // Date input with jQuery UI DatePicker
                el('input', {
                    key: 'input',
                    ref: inputRef,
                    id: 'preorder-date-input',
                    type: 'text',
                    className: 'wc-block-components-text-input__input',
                    value: selectedDate,
                    onChange: handleDateChange,
                    placeholder: translations.preOrderDatePlaceholder,
                    readOnly: true // Let jQuery UI handle the input
                })
            ]);
        };
        
        /**
         * Allow Render function following WooCommerce documentation pattern.
         */
        const render = () => {
            return el(ExperimentalOrderMeta, {
                name: 'preorder-date-picker'
            }, el('div', {
                className: 'wc-block-components-totals-wrapper'
            }, el(PreOrderDatePickerComponent)));
        };
        
        /**
         * Register the plugin using the official Slot and Fill pattern
         * This follows the exact pattern from WooCommerce documentation
         */
        registerPlugin('preorder-date-picker', {
            render,
            scope: 'woocommerce-checkout',
        });
        
    } else {
        /**
         * Fallback: Wait for dependencies to load
         */
        const checkDependencies = () => {
            if (typeof window.wp !== 'undefined' && 
                window.wp.plugins && 
                window.wp.element && 
                window.wp.data && 
                window.wp.i18n && 
                window.wc && 
                window.wc.blocksCheckout) {
                
                // Re-run the initialization
                const script = document.createElement('script');
                script.innerHTML = arguments.callee.toString().replace('checkDependencies', 'preOrderDatePickerInit');
                document.head.appendChild(script);
                
            } else {
                setTimeout(checkDependencies, 100);
            }
        };  
        
        // Start checking for dependencies
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', checkDependencies);
        } else {
            checkDependencies();
        }
    }
})();
