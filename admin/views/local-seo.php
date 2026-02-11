<?php
/**
 * Local SEO View
 */

if (!defined('ABSPATH')) {
    exit;
}

$local_seo = new SSF_Local_SEO();
$settings = $local_seo->get_settings();
$business_types = $local_seo->get_business_types();
$days = $local_seo->get_days();
$locations = $local_seo->get_locations();
?>
<div class="wrap ssf-wrap">
    <h1 class="ssf-page-title">
        <span class="dashicons dashicons-location"></span>
        <?php esc_html_e('Local SEO', 'smart-seo-fixer'); ?>
        <span class="ssf-version">v<?php echo esc_html(SSF_VERSION); ?></span>
    </h1>
    
    <p class="ssf-intro"><?php esc_html_e('Configure your local business information to generate LocalBusiness schema markup. This helps Google show your business in local search results and Google Maps.', 'smart-seo-fixer'); ?></p>
    
    <form id="local-seo-form">
        <!-- Enable Local SEO -->
        <div class="ssf-card">
            <div class="ssf-card-header">
                <h2>
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e('Enable Local SEO', 'smart-seo-fixer'); ?>
                </h2>
            </div>
            <div class="ssf-card-body">
                <label class="ssf-toggle">
                    <input type="checkbox" name="enabled" value="1" <?php checked($settings['enabled']); ?>>
                    <span class="ssf-toggle-slider"></span>
                    <span class="ssf-toggle-label"><?php esc_html_e('Enable Local Business Schema', 'smart-seo-fixer'); ?></span>
                </label>
                <p class="description"><?php esc_html_e('When enabled, LocalBusiness schema will be added to your homepage.', 'smart-seo-fixer'); ?></p>
            </div>
        </div>
        
        <!-- Business Information -->
        <div class="ssf-card">
            <div class="ssf-card-header">
                <h2>
                    <span class="dashicons dashicons-building"></span>
                    <?php esc_html_e('Business Information', 'smart-seo-fixer'); ?>
                </h2>
            </div>
            <div class="ssf-card-body">
                <table class="form-table">
                    <tr>
                        <th><label for="business_name"><?php esc_html_e('Business Name', 'smart-seo-fixer'); ?></label></th>
                        <td>
                            <input type="text" name="business_name" id="business_name" 
                                   value="<?php echo esc_attr($settings['business_name']); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="business_type"><?php esc_html_e('Business Type', 'smart-seo-fixer'); ?></label></th>
                        <td>
                            <select name="business_type" id="business_type">
                                <?php foreach ($business_types as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['business_type'], $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="description"><?php esc_html_e('Description', 'smart-seo-fixer'); ?></label></th>
                        <td>
                            <textarea name="description" id="description" rows="3" class="large-text"><?php echo esc_textarea($settings['description']); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="phone"><?php esc_html_e('Phone Number', 'smart-seo-fixer'); ?></label></th>
                        <td>
                            <input type="tel" name="phone" id="phone" 
                                   value="<?php echo esc_attr($settings['phone']); ?>" class="regular-text"
                                   placeholder="+1 (555) 123-4567">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="email"><?php esc_html_e('Email', 'smart-seo-fixer'); ?></label></th>
                        <td>
                            <input type="email" name="email" id="email" 
                                   value="<?php echo esc_attr($settings['email']); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="price_range"><?php esc_html_e('Price Range', 'smart-seo-fixer'); ?></label></th>
                        <td>
                            <select name="price_range" id="price_range">
                                <option value=""><?php esc_html_e('Select...', 'smart-seo-fixer'); ?></option>
                                <option value="$" <?php selected($settings['price_range'], '$'); ?>>$ (Budget)</option>
                                <option value="$$" <?php selected($settings['price_range'], '$$'); ?>>$$ (Moderate)</option>
                                <option value="$$$" <?php selected($settings['price_range'], '$$$'); ?>>$$$ (Expensive)</option>
                                <option value="$$$$" <?php selected($settings['price_range'], '$$$$'); ?>>$$$$ (Luxury)</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Address -->
        <div class="ssf-card">
            <div class="ssf-card-header">
                <h2>
                    <span class="dashicons dashicons-location-alt"></span>
                    <?php esc_html_e('Address', 'smart-seo-fixer'); ?>
                </h2>
            </div>
            <div class="ssf-card-body">
                <table class="form-table">
                    <tr>
                        <th><label for="address_street"><?php esc_html_e('Street Address', 'smart-seo-fixer'); ?></label></th>
                        <td>
                            <input type="text" name="address[street]" id="address_street" 
                                   value="<?php echo esc_attr($settings['address']['street']); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="address_city"><?php esc_html_e('City', 'smart-seo-fixer'); ?></label></th>
                        <td>
                            <input type="text" name="address[city]" id="address_city" 
                                   value="<?php echo esc_attr($settings['address']['city']); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="address_state"><?php esc_html_e('State/Province', 'smart-seo-fixer'); ?></label></th>
                        <td>
                            <input type="text" name="address[state]" id="address_state" 
                                   value="<?php echo esc_attr($settings['address']['state']); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="address_zip"><?php esc_html_e('ZIP/Postal Code', 'smart-seo-fixer'); ?></label></th>
                        <td>
                            <input type="text" name="address[zip]" id="address_zip" 
                                   value="<?php echo esc_attr($settings['address']['zip']); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="address_country"><?php esc_html_e('Country', 'smart-seo-fixer'); ?></label></th>
                        <td>
                            <input type="text" name="address[country]" id="address_country" 
                                   value="<?php echo esc_attr($settings['address']['country']); ?>" class="regular-text"
                                   placeholder="US">
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Coordinates', 'smart-seo-fixer'); ?></label></th>
                        <td>
                            <div class="ssf-coords-row">
                                <input type="text" name="geo[latitude]" 
                                       value="<?php echo esc_attr($settings['geo']['latitude']); ?>" 
                                       placeholder="<?php esc_attr_e('Latitude', 'smart-seo-fixer'); ?>" class="small-text">
                                <input type="text" name="geo[longitude]" 
                                       value="<?php echo esc_attr($settings['geo']['longitude']); ?>" 
                                       placeholder="<?php esc_attr_e('Longitude', 'smart-seo-fixer'); ?>" class="small-text">
                            </div>
                            <p class="description">
                                <?php esc_html_e('Optional. Find coordinates at', 'smart-seo-fixer'); ?>
                                <a href="https://www.google.com/maps" target="_blank">Google Maps</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Business Hours -->
        <div class="ssf-card">
            <div class="ssf-card-header">
                <h2>
                    <span class="dashicons dashicons-clock"></span>
                    <?php esc_html_e('Business Hours', 'smart-seo-fixer'); ?>
                </h2>
            </div>
            <div class="ssf-card-body">
                <table class="ssf-hours-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Day', 'smart-seo-fixer'); ?></th>
                            <th><?php esc_html_e('Open', 'smart-seo-fixer'); ?></th>
                            <th><?php esc_html_e('Opening Time', 'smart-seo-fixer'); ?></th>
                            <th><?php esc_html_e('Closing Time', 'smart-seo-fixer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($days as $key => $label): 
                            $day_hours = $settings['hours'][$key] ?? [];
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($label); ?></strong></td>
                            <td>
                                <input type="checkbox" name="hours[<?php echo esc_attr($key); ?>][open]" value="1" 
                                       <?php checked(!empty($day_hours['open'])); ?>>
                            </td>
                            <td>
                                <input type="time" name="hours[<?php echo esc_attr($key); ?>][open_time]" 
                                       value="<?php echo esc_attr($day_hours['open_time'] ?? '09:00'); ?>">
                            </td>
                            <td>
                                <input type="time" name="hours[<?php echo esc_attr($key); ?>][close_time]" 
                                       value="<?php echo esc_attr($day_hours['close_time'] ?? '17:00'); ?>">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Service Areas -->
        <div class="ssf-card">
            <div class="ssf-card-header">
                <h2>
                    <span class="dashicons dashicons-admin-site-alt3"></span>
                    <?php esc_html_e('Service Areas', 'smart-seo-fixer'); ?>
                </h2>
            </div>
            <div class="ssf-card-body">
                <p class="description"><?php esc_html_e('List the cities or areas you serve (comma-separated).', 'smart-seo-fixer'); ?></p>
                <textarea name="service_areas" id="service_areas" rows="4" class="large-text" placeholder="Chicago, Naperville, Aurora, Joliet, Evanston"><?php echo esc_textarea(implode(', ', $settings['service_areas'])); ?></textarea>
            </div>
        </div>
        
        <!-- Practice Areas (for Law Firms) -->
        <div class="ssf-card ssf-card-legal" id="practice-areas-card" style="<?php echo (strpos($settings['business_type'], 'Legal') !== false || $settings['business_type'] === 'Attorney') ? '' : 'display:none;'; ?>">
            <div class="ssf-card-header">
                <h2>
                    <span class="dashicons dashicons-clipboard"></span>
                    <?php esc_html_e('Practice Areas (Law Firms)', 'smart-seo-fixer'); ?>
                </h2>
            </div>
            <div class="ssf-card-body">
                <p class="description"><?php esc_html_e('List your practice areas. This helps with legal SEO and rich snippets.', 'smart-seo-fixer'); ?></p>
                <textarea name="practice_areas" id="practice_areas" rows="4" class="large-text" placeholder="Personal Injury, Family Law, Criminal Defense, Estate Planning, Business Law"><?php echo esc_textarea(implode(', ', $settings['practice_areas'] ?? [])); ?></textarea>
                
                <h4 style="margin-top: 20px;"><?php esc_html_e('Common Practice Areas (click to add):', 'smart-seo-fixer'); ?></h4>
                <div class="ssf-practice-tags">
                    <?php 
                    $common_practices = [
                        'Personal Injury', 'Car Accidents', 'Slip and Fall', 'Medical Malpractice',
                        'Family Law', 'Divorce', 'Child Custody', 'Child Support',
                        'Criminal Defense', 'DUI/DWI', 'Drug Crimes', 'Domestic Violence',
                        'Estate Planning', 'Wills & Trusts', 'Probate',
                        'Business Law', 'Contracts', 'Business Formation',
                        'Real Estate', 'Immigration', 'Employment Law', 'Workers Compensation',
                        'Bankruptcy', 'Social Security Disability', 'Civil Litigation'
                    ];
                    foreach ($common_practices as $practice): ?>
                    <span class="ssf-practice-tag" data-practice="<?php echo esc_attr($practice); ?>"><?php echo esc_html($practice); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Social Profiles -->
        <div class="ssf-card">
            <div class="ssf-card-header">
                <h2>
                    <span class="dashicons dashicons-share"></span>
                    <?php esc_html_e('Social Profiles', 'smart-seo-fixer'); ?>
                </h2>
            </div>
            <div class="ssf-card-body">
                <table class="form-table">
                    <tr>
                        <th><label for="social_facebook"><?php esc_html_e('Facebook', 'smart-seo-fixer'); ?></label></th>
                        <td><input type="url" name="social[facebook]" id="social_facebook" value="<?php echo esc_url($settings['social']['facebook']); ?>" class="regular-text" placeholder="https://facebook.com/yourpage"></td>
                    </tr>
                    <tr>
                        <th><label for="social_twitter"><?php esc_html_e('Twitter/X', 'smart-seo-fixer'); ?></label></th>
                        <td><input type="url" name="social[twitter]" id="social_twitter" value="<?php echo esc_url($settings['social']['twitter']); ?>" class="regular-text" placeholder="https://twitter.com/yourhandle"></td>
                    </tr>
                    <tr>
                        <th><label for="social_instagram"><?php esc_html_e('Instagram', 'smart-seo-fixer'); ?></label></th>
                        <td><input type="url" name="social[instagram]" id="social_instagram" value="<?php echo esc_url($settings['social']['instagram']); ?>" class="regular-text" placeholder="https://instagram.com/yourhandle"></td>
                    </tr>
                    <tr>
                        <th><label for="social_linkedin"><?php esc_html_e('LinkedIn', 'smart-seo-fixer'); ?></label></th>
                        <td><input type="url" name="social[linkedin]" id="social_linkedin" value="<?php echo esc_url($settings['social']['linkedin']); ?>" class="regular-text" placeholder="https://linkedin.com/company/yourcompany"></td>
                    </tr>
                    <tr>
                        <th><label for="social_youtube"><?php esc_html_e('YouTube', 'smart-seo-fixer'); ?></label></th>
                        <td><input type="url" name="social[youtube]" id="social_youtube" value="<?php echo esc_url($settings['social']['youtube']); ?>" class="regular-text" placeholder="https://youtube.com/@yourchannel"></td>
                    </tr>
                    <tr>
                        <th><label for="social_yelp"><?php esc_html_e('Yelp', 'smart-seo-fixer'); ?></label></th>
                        <td><input type="url" name="social[yelp]" id="social_yelp" value="<?php echo esc_url($settings['social']['yelp']); ?>" class="regular-text" placeholder="https://yelp.com/biz/yourbusiness"></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <p class="submit">
            <button type="submit" class="button button-primary button-large">
                <?php esc_html_e('Save Local SEO Settings', 'smart-seo-fixer'); ?>
            </button>
            <span class="spinner" style="float: none;"></span>
            <span class="ssf-save-status"></span>
        </p>
    </form>
    
    <!-- Multiple Locations -->
    <div class="ssf-card">
        <div class="ssf-card-header">
            <h2>
                <span class="dashicons dashicons-location"></span>
                <?php esc_html_e('Additional Locations', 'smart-seo-fixer'); ?>
            </h2>
            <button type="button" class="button" id="add-location-btn">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Add Location', 'smart-seo-fixer'); ?>
            </button>
        </div>
        <div class="ssf-card-body">
            <p class="description"><?php esc_html_e('If you have multiple business locations, add them here. Each location will get its own schema markup.', 'smart-seo-fixer'); ?></p>
            
            <div id="locations-list">
                <?php if (empty($locations)): ?>
                <p class="ssf-empty"><?php esc_html_e('No additional locations added yet.', 'smart-seo-fixer'); ?></p>
                <?php else: ?>
                <?php foreach ($locations as $location): ?>
                <div class="ssf-location-item" data-id="<?php echo esc_attr($location['id']); ?>">
                    <div class="ssf-location-info">
                        <strong><?php echo esc_html($location['name']); ?></strong>
                        <span><?php echo esc_html($location['address']['city'] . ', ' . $location['address']['state']); ?></span>
                    </div>
                    <div class="ssf-location-actions">
                        <button type="button" class="button button-small edit-location-btn"><?php esc_html_e('Edit', 'smart-seo-fixer'); ?></button>
                        <button type="button" class="button button-small delete-location-btn"><?php esc_html_e('Delete', 'smart-seo-fixer'); ?></button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.ssf-intro {
    font-size: 14px;
    color: #666;
    margin-bottom: 20px;
}

.ssf-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.ssf-toggle-slider {
    width: 50px;
    height: 26px;
    background: #ccc;
    border-radius: 13px;
    position: relative;
    transition: background 0.3s;
}

.ssf-toggle-slider::after {
    content: '';
    width: 22px;
    height: 22px;
    background: white;
    border-radius: 50%;
    position: absolute;
    top: 2px;
    left: 2px;
    transition: left 0.3s;
}

.ssf-toggle input:checked + .ssf-toggle-slider {
    background: #2563eb;
}

.ssf-toggle input:checked + .ssf-toggle-slider::after {
    left: 26px;
}

.ssf-toggle input {
    display: none;
}

.ssf-coords-row {
    display: flex;
    gap: 10px;
}

.ssf-hours-table {
    width: 100%;
    border-collapse: collapse;
}

.ssf-hours-table th,
.ssf-hours-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}

.ssf-hours-table input[type="time"] {
    width: 130px;
}

.ssf-location-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 6px;
    margin-bottom: 10px;
}

.ssf-location-info strong {
    display: block;
}

.ssf-location-info span {
    font-size: 13px;
    color: #666;
}

.ssf-location-actions {
    display: flex;
    gap: 5px;
}

.ssf-empty {
    text-align: center;
    color: #666;
    padding: 20px;
}

/* Practice Areas Tags */
.ssf-practice-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}

.ssf-practice-tag {
    background: #e8f4fc;
    color: #1e40af;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid #bfdbfe;
}

.ssf-practice-tag:hover {
    background: #1e40af;
    color: white;
}

.ssf-practice-tag.added {
    background: #22c55e;
    color: white;
    border-color: #22c55e;
}

.ssf-card-legal {
    border-left: 4px solid #1e40af;
}

.ssf-card-legal .ssf-card-header h2 {
    color: #1e40af;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Save form
    $('#local-seo-form').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var $spinner = $(this).find('.spinner');
        var $status = $(this).find('.ssf-save-status');
        
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        
        var formData = $(this).serializeArray();
        formData.push({name: 'action', value: 'ssf_save_local_seo'});
        formData.push({name: 'nonce', value: ssfAdmin.nonce});
        
        $.post(ssfAdmin.ajax_url, formData, function(response) {
            $btn.prop('disabled', false);
            $spinner.removeClass('is-active');
            
            if (response.success) {
                $status.html('<span style="color: #46b450;">✓ ' + response.data.message + '</span>');
            } else {
                $status.html('<span style="color: #dc3232;">✗ ' + response.data.message + '</span>');
            }
            
            setTimeout(function() {
                $status.html('');
            }, 3000);
        });
    });
    
    // Add location button
    $('#add-location-btn').on('click', function() {
        alert('<?php esc_html_e('Location editor coming soon! For now, use the main business address.', 'smart-seo-fixer'); ?>');
    });
    
    // Delete location
    $(document).on('click', '.delete-location-btn', function() {
        if (!confirm('<?php esc_html_e('Delete this location?', 'smart-seo-fixer'); ?>')) {
            return;
        }
        
        var $item = $(this).closest('.ssf-location-item');
        var locationId = $item.data('id');
        
        $.post(ssfAdmin.ajax_url, {
            action: 'ssf_delete_location',
            nonce: ssfAdmin.nonce,
            location_id: locationId
        }, function(response) {
            if (response.success) {
                $item.fadeOut(function() {
                    $(this).remove();
                });
            }
        });
    });
    
    // Show/hide practice areas based on business type
    $('#business_type').on('change', function() {
        var type = $(this).val();
        var isLegal = (type === 'LegalService' || type === 'Attorney' || type === 'Notary');
        
        if (isLegal) {
            $('#practice-areas-card').slideDown();
        } else {
            $('#practice-areas-card').slideUp();
        }
    });
    
    // Practice area tags - click to add
    $('.ssf-practice-tag').on('click', function() {
        var practice = $(this).data('practice');
        var $textarea = $('#practice_areas');
        var current = $textarea.val().trim();
        
        // Check if already added
        var practices = current.split(',').map(function(p) { return p.trim().toLowerCase(); });
        
        if (practices.indexOf(practice.toLowerCase()) !== -1) {
            // Already exists - remove it
            var newPractices = current.split(',').map(function(p) { return p.trim(); }).filter(function(p) {
                return p.toLowerCase() !== practice.toLowerCase();
            });
            $textarea.val(newPractices.join(', '));
            $(this).removeClass('added');
        } else {
            // Add it
            if (current) {
                $textarea.val(current + ', ' + practice);
            } else {
                $textarea.val(practice);
            }
            $(this).addClass('added');
        }
    });
    
    // Mark already-added practice areas on page load
    var currentPractices = $('#practice_areas').val().toLowerCase();
    if (currentPractices) {
        $('.ssf-practice-tag').each(function() {
            var practice = $(this).data('practice').toLowerCase();
            if (currentPractices.indexOf(practice) !== -1) {
                $(this).addClass('added');
            }
        });
    }
});
</script>

