(function($, wp, _) {
    'use strict';

    // Ensure the media object exists
    if (!wp.media || !wp.media.view) {
        console.warn('Media Meta Generator: wp.media not found');
        return;
    }

    // Track which views have been extended to avoid double-extension
    var extendedViews = {};

    /**
     * Build markup for the Media Meta Generator controls
     */
    function getMediaMetaGeneratorControlsHTML() {
        return `<div class="media-meta-generator-controls">
                    <h3>
                        <a class="media-meta-generator-fcw-logo" href="https://fourthcoastweb.com" target="_blank" rel="noopener">
                            <img
                                class="media-meta-generator-fcw-logo__img"
                                src="${media_meta_generator_vars.pluginUrl}assets/img/fourth-coast-web-logo.png"
                                alt="Fourth Coast Web Logo"
                            />
                        </a>
                        <span><span class="media-meta-generator-title">Image Metadata Generator</span> <br />Instantly populate the alt text, title, and description fields below.</span>
                    </h3>
                    <details>
                        <summary><span class="details-summary">Context Keywords</span></summary>
                        <label class="setting">
                            <p>Add specific keywords to guide the AI and include in the generated text. Separate keywords with commas.</p>
                            <input type="text" class="media-meta-generator-keywords" value="" placeholder="e.g. sunset, happy, logo" />
                        </label>
                        <div class="media-meta-generator-include-keywords">
                            <p>Include the keywords in the generated:</p>
                            <div class="include-keywords-checkboxes">
                                <span class="setting-include-keywords">
                                    <input type="checkbox" id="media-meta-generator-include-keywords-alt" class="media-meta-generator-include-keywords-alt" checked />
                                    <label for="media-meta-generator-include-keywords-alt">Alt Text</label>
                                </span>

                                <span class="setting-include-keywords">
                                    <input type="checkbox" id="media-meta-generator-include-keywords-title" class="media-meta-generator-include-keywords-title" />
                                    <label for="media-meta-generator-include-keywords-title">Title</label>
                                </span>

                                <span class="setting-include-keywords">
                                    <input type="checkbox" id="media-meta-generator-include-keywords-description" class="media-meta-generator-include-keywords-description" checked />
                                    <label for="media-meta-generator-include-keywords-description">Description</label>
                                </span>
                            </div>
                        </div>
                    </details>
                    <div class="actions">
                        <button type="button" class="button button-secondary media-meta-generator-generate-btn">${media_meta_generator_vars.strings.generate}</button>
                        <span class="spinner"></span>
                    </div>
                </div>`;
    }

    /**
     * Inject Media Meta Generator controls into the view
     */
    function injectMediaMetaGeneratorControls(view) {
        // Avoid duplicate injection
        if ( view.$('.media-meta-generator-controls').length ) {
            return;
        }

        var html = getMediaMetaGeneratorControlsHTML();

        // setTimeout ensures the DOM is ready
        setTimeout(function() {
            // Try multiple selectors for different modal contexts
            var $target = null;
            
            // Try .settings container (Media Library grid view form fields)
            $target = view.$('.settings');
            if ( $target.length ) {
                $target.before( html );
                return;
            }
            
            // Try attachment-info section
            $target = view.$('.attachment-info');
            if ( $target.length ) {
                $target.after( html );
                return;
            }
            
            // Try .attachment-details descendant
            $target = view.$('.attachment-details');
            if ( $target.length ) {
                $target.prepend( html );
                return;
            }
            
            // If view.el IS the .attachment-details, prepend directly
            if ( view.$el.hasClass('attachment-details') ) {
                view.$el.prepend( html );
                return;
            }
            
            // Fallback: prepend to the view element itself
            view.$el.prepend( html );
            
        }, 50);
    }

    /**
     * Handle the "Generate" button click
     */
    function handleGenerateAltText(event, view) {
        event.preventDefault();

        // Check file extension
        var filename = view.model.get('filename');
        var allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif'];
        var extension = filename.slice( (filename.lastIndexOf(".") - 1 >>> 0) + 2 ).toLowerCase();

        if ( allowedExtensions.indexOf( extension ) === -1 ) {
             alert( media_meta_generator_vars.strings.invalid_filetype );
             return;
        }

        var attachmentId = view.model.get('id');
        var keywords = view.$('.media-meta-generator-keywords').val();
        var $btn = view.$('.media-meta-generator-generate-btn');
        var $spinner = view.$('.media-meta-generator-controls .spinner');

        // Get flags (optional keywords to include in output)
        var includeKeywordsInAlt = view.$('.media-meta-generator-include-keywords-alt').is(':checked');
        var includeKeywordsInTitle = view.$('.media-meta-generator-include-keywords-title').is(':checked');
        var includeKeywordsInDesc = view.$('.media-meta-generator-include-keywords-description').is(':checked');

        // Loading state
        $btn.prop('disabled', true).text( media_meta_generator_vars.strings.generating );
        $spinner.addClass('is-active');

        // Send AJAX Request
        wp.ajax.post( 'media_meta_generator_generate_alt', {
            nonce: media_meta_generator_vars.nonce,
            attachment_id: attachmentId,
            keywords: keywords,
            include_keywords_in_alt: includeKeywordsInAlt,
            include_keywords_in_title: includeKeywordsInTitle,
            include_keywords_in_description: includeKeywordsInDesc
        })
        .done( function( response ) {
            
            // Update the Backbone model (triggers internal events)
            if ( response.alt_text ) {
                view.model.set( 'alt', response.alt_text );
            }
            
            if ( response.title ) {
                view.model.set( 'title', response.title );
            }
            
            if ( response.description ) {
                view.model.set( 'description', response.description );
            }
            
            // CRITICAL: Also update the DOM input fields directly
            // Try multiple selectors for different contexts
            var $altField = view.$('[data-setting="alt"]');
            var $titleField = view.$('[data-setting="title"]');
            var $descField = view.$('[data-setting="description"]');
            
            // Handle case where data-setting is on the container (label)
            if ( $altField.length && ! $altField.is(':input') ) {
                $altField = $altField.find('input, textarea');
            }
            if ( $titleField.length && ! $titleField.is(':input') ) {
                $titleField = $titleField.find('input, textarea');
            }
            if ( $descField.length && ! $descField.is(':input') ) {
                $descField = $descField.find('input, textarea');
            }
            
            // Also check the global document for modal fields (some modals use different scopes)
            if ( !$altField.length ) {
                $altField = $('.media-modal [data-setting="alt"]');
                if ( $altField.length && ! $altField.is(':input') ) {
                    $altField = $altField.find('input, textarea');
                }
            }
            if ( !$titleField.length ) {
                $titleField = $('.media-modal [data-setting="title"]');
                if ( $titleField.length && ! $titleField.is(':input') ) {
                    $titleField = $titleField.find('input, textarea');
                }
            }
            if ( !$descField.length ) {
                $descField = $('.media-modal [data-setting="description"]');
                if ( $descField.length && ! $descField.is(':input') ) {
                    $descField = $descField.find('input, textarea');
                }
            }
            
            // Update field values and trigger change for autosave
            if ( $altField.length && response.alt_text ) {
                $altField.val( response.alt_text ).trigger('change');
            }
            
            if ( $titleField.length && response.title ) {
                $titleField.val( response.title ).trigger('change');
            }
            
            if ( $descField.length && response.description ) {
                $descField.val( response.description ).trigger('change');
            }
            
            // Trigger model save to persist to server
            view.model.save();
            
            $btn.text( 'Success!' );
            setTimeout( function() {
                $btn.prop('disabled', false).text( media_meta_generator_vars.strings.generate );
            }, 2000 );
        })
        .fail( function( response ) {
            console.error('Media Meta Generator: Error', response);
            alert( media_meta_generator_vars.strings.error + ( response.message || 'Unknown error' ) );
            $btn.prop('disabled', false).text( media_meta_generator_vars.strings.generate );
        })
        .always( function() {
            $spinner.removeClass('is-active');
        });
    }

    /**
     * Create the extension mixin for a given parent view
     */
    function createMediaMetaGeneratorExtension(ParentView) {
        return ParentView.extend({
            events: _.extend( {}, ParentView.prototype.events, {
                'click .media-meta-generator-generate-btn': 'generateAltText'
            }),

            render: function() {
                // Call the parent render method
                ParentView.prototype.render.apply( this, arguments );
                
                // Inject our controls
                injectMediaMetaGeneratorControls( this );

                return this;
            },

            generateAltText: function( event ) {
                handleGenerateAltText( event, this );
            }
        });
    }

    /**
     * Perform initial extension of views that exist at load time
     */
    function extendExistingViews() {
        var OriginalDetails = wp.media.view.Attachment.Details;
        
        // Always extend the base Details view
        if ( OriginalDetails && !extendedViews['Details'] ) {
            wp.media.view.Attachment.Details = createMediaMetaGeneratorExtension( OriginalDetails );
            extendedViews['Details'] = true;
        }
        
        // Extend TwoColumn if it exists
        var TwoColumn = wp.media.view.Attachment.Details.TwoColumn;
        if ( TwoColumn && !extendedViews['TwoColumn'] ) {
            wp.media.view.Attachment.Details.TwoColumn = createMediaMetaGeneratorExtension( TwoColumn );
            extendedViews['TwoColumn'] = true;
        }
    }

    /**
     * Hook into media frame to extend views when modal opens
     * This catches cases where TwoColumn is defined lazily
     */
    function hookMediaFrame() {
        // Hook into wp.media() to extend views when frames are created
        var originalMedia = wp.media;
        
        wp.media = function() {
            var frame = originalMedia.apply( this, arguments );
            
            // When frame opens, ensure views are extended
            if ( frame && frame.on ) {
                frame.on('open', function() {
                    // Give WordPress a quick moment to define all the views
                    setTimeout(function() {
                        extendExistingViews();
                    }, 10);
                });
            }
            
            return frame;
        };
        
        // Copy over any static properties and methods
        for ( var prop in originalMedia ) {
            if ( originalMedia.hasOwnProperty(prop) ) {
                wp.media[prop] = originalMedia[prop];
            }
        }
        
        // Also hook into the global frame if it exists
        $(document).on('click', '.media-modal', function() {
            setTimeout(function() {
                extendExistingViews();
            }, 100);
        });
    }

    // Initial extension (for views that exist at load time)
    extendExistingViews();
    
    // Hook media frame for lazy-loaded views
    hookMediaFrame();

})(jQuery, wp, _);