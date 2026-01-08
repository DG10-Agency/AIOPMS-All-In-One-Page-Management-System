<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Schema types constants
define('AIOPMS_SCHEMA_FAQ', 'faq');
define('AIOPMS_SCHEMA_BLOG', 'blog');
define('AIOPMS_SCHEMA_ARTICLE', 'article');
define('AIOPMS_SCHEMA_SERVICE', 'service');
define('AIOPMS_SCHEMA_PRODUCT', 'product');
define('AIOPMS_SCHEMA_ORGANIZATION', 'organization');
define('AIOPMS_SCHEMA_LOCAL_BUSINESS', 'local_business');
define('AIOPMS_SCHEMA_WEBPAGE', 'webpage');
define('AIOPMS_SCHEMA_HOWTO', 'howto');
define('AIOPMS_SCHEMA_REVIEW', 'review');
define('AIOPMS_SCHEMA_EVENT', 'event');

/**
 * AI-powered schema data extraction
 * This function asks the AI to extract specific schema properties from the page content.
 * @param int $post_id The post ID.
 * @param string $schema_type The detected schema type.
 * @return array|false The extracted schema properties or false on failure.
 */
function aiopms_ai_extract_schema_data($post_id, $schema_type) {
    $post = get_post($post_id);
    if (!$post) {
        return false;
    }

    $provider = get_option('aiopms_ai_provider', 'openai');
    $api_key = get_option('aiopms_' . $provider . '_api_key');

    if (empty($api_key)) {
        return false;
    }

    $content = wp_strip_all_tags($post->post_content);
    $title = $post->post_title;
    $business_knowledge = get_option('aiopms_business_description', '');
    
    // Limit content for API efficiency
    if (strlen($content) > 3000) {
        $content = substr($content, 0, 3000) . '...';
    }

    // Define expected properties per schema type
    // Define expected properties per schema type
    $property_prompts = [
        'faq' => 'Extract questions and answers as a JSON array: [{"question": "...", "answer": "..."}, ...]',
        'service' => 'Extract: {"serviceType": "...", "areaServed": "...", "features": ["..."], "offers": {"price": "...", "priceCurrency": "...", "description": "..."}, "audience": "...", "provider": "..."}',
        'product' => 'Extract: {"brand": "...", "sku": "...", "price": "...", "priceCurrency": "...", "availability": "InStock/OutOfStock", "features": ["..."], "color": "...", "material": "...", "condition": "NewCondition"}',
        'review' => 'Extract: {"ratingValue": 1-5, "itemReviewed": "...", "reviewSummary": "...", "author": "...", "pros": ["..."], "cons": ["..."]}',
        'howto' => 'Extract: {"steps": [{"name": "...", "text": "...", "image": "..."}], "totalTime": "PT...M", "estimatedCost": "...", "supply": ["..."], "tool": ["..."]}',
        'event' => 'Extract: {"startDate": "YYYY-MM-DD", "endDate": "YYYY-MM-DD", "location": "...", "organizer": "...", "ticketUrl": "...", "performer": "...", "eventStatus": "EventScheduled"}',
        'article' => 'Extract: {"wordCount": ..., "keywords": ["..."], "articleSection": "...", "alternativeHeadline": "...", "proficiencyLevel": "..."}',
        'blog' => 'Extract: {"keywords": ["..."], "articleSection": "...", "audience": "..."}',
    ];

    if (!isset($property_prompts[$schema_type])) {
        return false; // No AI extraction for this type
    }

    $prompt = "You are a schema.org expert. Analyze the following webpage content and extract specific data for a '$schema_type' schema.

Business Context: $business_knowledge

Page Title: $title
Page Content: $content

" . $property_prompts[$schema_type] . "

IMPORTANT: Return ONLY a valid JSON object. Do not include any explanations, markdown formatting, or extra text. If a value is not found, use null.";

    $result = null;
    switch ($provider) {
        case 'openai':
            $result = aiopms_ai_call_openai_json($prompt, $api_key);
            break;
        case 'gemini':
            $result = aiopms_ai_call_gemini_json($prompt, $api_key);
            break;
        case 'deepseek':
            $result = aiopms_ai_call_deepseek_json($prompt, $api_key);
            break;
    }

    return $result;
}

// OpenAI JSON extraction helper
function aiopms_ai_call_openai_json($prompt, $api_key) {
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $api_key],
        'body' => json_encode([
            'model' => 'gpt-3.5-turbo',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.2,
            'max_tokens' => 500,
        ]),
        'timeout' => 30,
    ]);
    if (is_wp_error($response)) return false;
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['choices'][0]['message']['content'])) {
        $json_str = trim($body['choices'][0]['message']['content']);
        $data = json_decode($json_str, true);
        return is_array($data) ? $data : false;
    }
    return false;
}

// Gemini JSON extraction helper
function aiopms_ai_call_gemini_json($prompt, $api_key) {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $api_key;
    $response = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode(['contents' => [['parts' => [['text' => $prompt]]]], 'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 500]]),
        'timeout' => 30,
    ]);
    if (is_wp_error($response)) return false;
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
        $json_str = trim($body['candidates'][0]['content']['parts'][0]['text']);
        // Strip potential markdown fences
        $json_str = preg_replace('/^```json\s*|\s*```$/i', '', $json_str);
        $data = json_decode($json_str, true);
        return is_array($data) ? $data : false;
    }
    return false;
}

// DeepSeek JSON extraction helper
function aiopms_ai_call_deepseek_json($prompt, $api_key) {
    $response = wp_remote_post('https://api.deepseek.com/v1/chat/completions', [
        'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $api_key],
        'body' => json_encode([
            'model' => 'deepseek-chat',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.2,
            'max_tokens' => 500,
        ]),
        'timeout' => 30,
    ]);
    if (is_wp_error($response)) return false;
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['choices'][0]['message']['content'])) {
        $json_str = trim($body['choices'][0]['message']['content']);
        $data = json_decode($json_str, true);
        return is_array($data) ? $data : false;
    }
    return false;
}


// AI-powered content analysis for schema detection
function aiopms_ai_analyze_content_for_schema($post_id) {
    $post = get_post($post_id);
    if (!$post) {
        return false;
    }

    $provider = get_option('aiopms_ai_provider', 'openai');
    $api_key = get_option('aiopms_' . $provider . '_api_key');

    if (empty($api_key)) {
        return false;
    }

    $content = wp_strip_all_tags($post->post_content);
    $title = $post->post_title;
    $excerpt = $post->post_excerpt;
    
    // Limit content length for API efficiency
    if (strlen($content) > 2000) {
        $content = substr($content, 0, 2000) . '...';
    }

    $valid_schema_types = [
        'faq', 'blog', 'article', 'service', 'product', 
        'organization', 'local_business', 'howto', 'review', 'event', 'webpage'
    ];

    switch ($provider) {
        case 'openai':
            return aiopms_ai_analyze_content_openai($title, $content, $excerpt, $api_key, $valid_schema_types);
        case 'gemini':
            return aiopms_ai_analyze_content_gemini($title, $content, $excerpt, $api_key, $valid_schema_types);
        case 'deepseek':
            return aiopms_ai_analyze_content_deepseek($title, $content, $excerpt, $api_key, $valid_schema_types);
        default:
            return false;
    }
}

// OpenAI content analysis for schema
function aiopms_ai_analyze_content_openai($title, $content, $excerpt, $api_key, $valid_schema_types) {
    $url = 'https://api.openai.com/v1/chat/completions';
    
    $prompt = "Analyze the following webpage content and determine the most appropriate Schema.org type for SEO optimization.

Title: {$title}
Content: {$content}
Excerpt: {$excerpt}

Valid schema types: " . implode(', ', $valid_schema_types) . "

Consider the content structure, purpose, and user intent. Return ONLY the most appropriate schema type from the valid list above. Examples:
- FAQ pages with questions/answers: faq
- Step-by-step tutorials: howto  
- Product/service reviews: review
- Events, webinars, conferences: event
- Blog posts: blog
- Articles/guides: article
- Service pages: service
- Product pages: product
- Company info: organization
- Local business info: local_business
- General pages: webpage

Return only the schema type name, nothing else.";

    $body = json_encode([
        'model' => 'gpt-3.5-turbo',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.3,
        'max_tokens' => 50,
    ]);

    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => $body,
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($response_body['choices'][0]['message']['content'])) {
        $schema_type = trim(strtolower($response_body['choices'][0]['message']['content']));
        if (in_array($schema_type, $valid_schema_types)) {
            return $schema_type;
        }
    }

    return false;
}

// Gemini content analysis for schema
function aiopms_ai_analyze_content_gemini($title, $content, $excerpt, $api_key, $valid_schema_types) {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $api_key;
    
    $prompt = "Analyze the following webpage content and determine the most appropriate Schema.org type for SEO optimization.

Title: {$title}
Content: {$content}
Excerpt: {$excerpt}

Valid schema types: " . implode(', ', $valid_schema_types) . "

Consider the content structure, purpose, and user intent. Return ONLY the most appropriate schema type from the valid list above. Examples:
- FAQ pages with questions/answers: faq
- Step-by-step tutorials: howto  
- Product/service reviews: review
- Events, webinars, conferences: event
- Blog posts: blog
- Articles/guides: article
- Service pages: service
- Product pages: product
- Company info: organization
- Local business info: local_business
- General pages: webpage

Return only the schema type name, nothing else.";

    $body = json_encode([
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => [
            'temperature' => 0.3,
            'maxOutputTokens' => 50,
        ]
    ]);

    $response = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => $body,
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($response_body['candidates'][0]['content']['parts'][0]['text'])) {
        $schema_type = trim(strtolower($response_body['candidates'][0]['content']['parts'][0]['text']));
        if (in_array($schema_type, $valid_schema_types)) {
            return $schema_type;
        }
    }

    return false;
}

// DeepSeek content analysis for schema
function aiopms_ai_analyze_content_deepseek($title, $content, $excerpt, $api_key, $valid_schema_types) {
    $url = 'https://api.deepseek.com/v1/chat/completions';
    
    $prompt = "Analyze the following webpage content and determine the most appropriate Schema.org type for SEO optimization.

Title: {$title}
Content: {$content}
Excerpt: {$excerpt}

Valid schema types: " . implode(', ', $valid_schema_types) . "

Consider the content structure, purpose, and user intent. Return ONLY the most appropriate schema type from the valid list above. Examples:
- FAQ pages with questions/answers: faq
- Step-by-step tutorials: howto  
- Product/service reviews: review
- Events, webinars, conferences: event
- Blog posts: blog
- Articles/guides: article
- Service pages: service
- Product pages: product
- Company info: organization
- Local business info: local_business
- General pages: webpage

Return only the schema type name, nothing else.";

    $body = json_encode([
        'model' => 'deepseek-chat',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.3,
        'max_tokens' => 50,
    ]);

    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body' => $body,
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($response_body['choices'][0]['message']['content'])) {
        $schema_type = trim(strtolower($response_body['choices'][0]['message']['content']));
        if (in_array($schema_type, $valid_schema_types)) {
            return $schema_type;
        }
    }

    return false;
}

// Detect schema type for a page (enhanced with AI analysis)
function aiopms_detect_schema_type($post_id) {
    $post = get_post($post_id);
    if (!$post) {
        return AIOPMS_SCHEMA_WEBPAGE;
    }

    // Check if handling a dynamic CPT
    if (function_exists('aiopms_is_dynamic_cpt') && aiopms_is_dynamic_cpt($post->post_type)) {
        // Try to infer schema from CPT slug
        $cpt_slug = strtolower($post->post_type);
        if (strpos($cpt_slug, 'product') !== false) return AIOPMS_SCHEMA_PRODUCT;
        if (strpos($cpt_slug, 'service') !== false) return AIOPMS_SCHEMA_SERVICE;
        if (strpos($cpt_slug, 'event') !== false) return AIOPMS_SCHEMA_EVENT;
        if (strpos($cpt_slug, 'review') !== false) return AIOPMS_SCHEMA_REVIEW;
        if (strpos($cpt_slug, 'faq') !== false) return AIOPMS_SCHEMA_FAQ;
        if (strpos($cpt_slug, 'article') !== false) return AIOPMS_SCHEMA_ARTICLE;
    }

    // Try AI analysis first
    $ai_schema_type = aiopms_ai_analyze_content_for_schema($post_id);
    if ($ai_schema_type) {
        return $ai_schema_type;
    }

    // Fallback to keyword-based detection
    $content = $post->post_content;
    $title = $post->post_title;
    $excerpt = $post->post_excerpt;

    // Check for FAQ content patterns
    if (aiopms_is_faq_page($content, $title)) {
        return AIOPMS_SCHEMA_FAQ;
    }

    // Check for HowTo content
    if (aiopms_is_howto_page($content, $title)) {
        return AIOPMS_SCHEMA_HOWTO;
    }

    // Check for Review content
    if (aiopms_is_review_page($content, $title)) {
        return AIOPMS_SCHEMA_REVIEW;
    }

    // Check for Event content
    if (aiopms_is_event_page($content, $title)) {
        return AIOPMS_SCHEMA_EVENT;
    }

    // Check for blog post
    if (aiopms_is_blog_post($post)) {
        return AIOPMS_SCHEMA_BLOG;
    }

    // Check for article
    if (aiopms_is_article($content, $title)) {
        return AIOPMS_SCHEMA_ARTICLE;
    }

    // Check for service page
    if (aiopms_is_service_page($title, $content)) {
        return AIOPMS_SCHEMA_SERVICE;
    }

    // Check for product page
    if (aiopms_is_product_page($title, $content)) {
        return AIOPMS_SCHEMA_PRODUCT;
    }

    // Check for organization page
    if (aiopms_is_organization_page($title)) {
        return AIOPMS_SCHEMA_ORGANIZATION;
    }

    // Check for local business page
    if (aiopms_is_local_business_page($title)) {
        return AIOPMS_SCHEMA_LOCAL_BUSINESS;
    }

    // Default to webpage
    return AIOPMS_SCHEMA_WEBPAGE;
}

// Check if content contains FAQ patterns
function aiopms_is_faq_page($content, $title) {
    // Check title for FAQ indicators
    $faq_keywords = ['faq', 'frequently asked', 'questions', 'q&a', 'help center'];
    foreach ($faq_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }

    // Check content for question-answer patterns
    $question_patterns = [
        '/<h[1-6][^>]*>.*\?.*<\/h[1-6]>/i',
        '/<strong>.*\?.*<\/strong>/i',
        '/<b>.*\?.*<\/b>/i',
        '/<p><strong>.*\?.*<\/strong><\/p>/i'
    ];

    foreach ($question_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return true;
        }
    }

    return false;
}

// Check if post is a blog post
function aiopms_is_blog_post($post) {
    if ($post->post_type === 'post') {
        return true;
    }

    // Check if page has blog-like characteristics
    $blog_categories = ['blog', 'news', 'article', 'post'];
    $categories = wp_get_post_categories($post->ID, ['fields' => 'names']);
    
    foreach ($categories as $category) {
        if (in_array(strtolower($category), $blog_categories)) {
            return true;
        }
    }

    return false;
}

// Check if content is an article
function aiopms_is_article($content, $title) {
    // Articles typically have longer content
    if (str_word_count(strip_tags($content)) > 500) {
        return true;
    }

    // Check for article indicators in title
    $article_keywords = ['guide', 'tutorial', 'how to', 'tips', 'review', 'analysis'];
    foreach ($article_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

// Check if page is a service page
function aiopms_is_service_page($title, $content) {
    $service_keywords = ['service', 'solution', 'package', 'offer', 'consulting', 'support'];
    foreach ($service_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }

    // Check content for service-related terms
    $service_content_keywords = ['pricing', 'features', 'benefits', 'what we offer'];
    foreach ($service_content_keywords as $keyword) {
        if (stripos($content, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

// Check if page is a product page
function aiopms_is_product_page($title, $content) {
    $product_keywords = ['product', 'item', 'buy', 'purchase', 'order', 'shop'];
    foreach ($product_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }

    // Check for price information
    if (preg_match('/\$\d+\.?\d*/', $content) || preg_match('/\d+\.?\d*\s*(USD|EUR|GBP|INR)/i', $content)) {
        return true;
    }

    return false;
}

// Check if page is an organization page
function aiopms_is_organization_page($title) {
    $org_keywords = ['about', 'company', 'team', 'mission', 'vision', 'values', 'history'];
    foreach ($org_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

// Check if page is a local business page
function aiopms_is_local_business_page($title) {
    $business_keywords = ['location', 'store', 'office', 'contact', 'address', 'hours', 'map'];
    foreach ($business_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

// Check if page is a HowTo/tutorial page
function aiopms_is_howto_page($content, $title) {
    // Check title for HowTo indicators
    $howto_keywords = ['how to', 'how-to', 'tutorial', 'guide', 'step by step', 'instructions', 'walkthrough'];
    foreach ($howto_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }

    // Check content for step patterns
    $step_patterns = [
        '/step\s+\d+/i',
        '/\d+\.\s*[A-Z]/',
        '/first\s*,?\s*second\s*,?\s*third/i',
        '/next\s*,?\s*then\s*,?\s*finally/i'
    ];

    foreach ($step_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return true;
        }
    }

    return false;
}

// Check if page is a review page
function aiopms_is_review_page($content, $title) {
    // Check title for review indicators
    $review_keywords = ['review', 'rating', 'testimonial', 'feedback', 'opinion', 'analysis'];
    foreach ($review_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }

    // Check content for review patterns
    $review_patterns = [
        '/\d+\/\d+\s*(stars?|rating)/i',
        '/pros?\s*and\s*cons?/i',
        '/recommend/i',
        '/overall\s*rating/i',
        '/my\s*experience/i'
    ];

    foreach ($review_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return true;
        }
    }

    return false;
}

// Check if page is an event page
function aiopms_is_event_page($content, $title) {
    // Check title for event indicators
    $event_keywords = ['event', 'conference', 'webinar', 'workshop', 'seminar', 'meeting', 'training', 'course'];
    foreach ($event_keywords as $keyword) {
        if (stripos($title, $keyword) !== false) {
            return true;
        }
    }

    // Check content for event patterns
    $event_patterns = [
        '/\d{1,2}\/\d{1,2}\/\d{4}/', // Date patterns
        '/\d{1,2}:\d{2}\s*(am|pm)/i', // Time patterns
        '/register\s*(now|here)/i',
        '/ticket/i',
        '/venue/i',
        '/speaker/i'
    ];

    foreach ($event_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return true;
        }
    }

    return false;
}

// Generate schema markup for a page
if (!function_exists('aiopms_generate_schema_markup')) {
    function aiopms_generate_schema_markup($post_id) {
        $schema_type = aiopms_detect_schema_type($post_id);
        $permalink = get_permalink($post_id);
        $site_url = home_url();
        
        // Initialize Graph
        $graph = [];

        // 1. Organization (Global Publisher)
        $org_schema = aiopms_get_organization_schema();
        $graph[] = $org_schema;

        // 2. WebSite (Global)
        $website_schema = aiopms_generate_website_schema();
        $graph[] = $website_schema;

        // 3. WebPage (Current Page)
        $webpage_schema = [
            '@type' => 'WebPage',
            '@id' => $permalink . '#webpage',
            'url' => $permalink,
            'name' => get_the_title($post_id),
            'description' => get_the_excerpt($post_id),
            'isPartOf' => ['@id' => $site_url . '/#website'],
            'publisher' => ['@id' => $site_url . '/#organization'],
            'inLanguage' => get_bloginfo('language'),
            'datePublished' => get_the_date('c', $post_id),
            'dateModified' => get_the_modified_date('c', $post_id),
            'breadcrumb' => ['@id' => $permalink . '#breadcrumb']
        ];
        
        // Add featured image to WebPage
        $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
        if ($thumbnail_url) {
            $webpage_schema['primaryImageOfPage'] = [
                '@type' => 'ImageObject',
                '@id' => $permalink . '#primaryimage',
                'url' => $thumbnail_url
            ];
            $webpage_schema['image'] = ['@id' => $permalink . '#primaryimage'];
        }

        // 4. BreadcrumbList
        $breadcrumb_schema = aiopms_generate_breadcrumb_schema($post_id);
        $graph[] = $breadcrumb_schema;

        // 5. Main Entity
        $main_entity = [];
        switch ($schema_type) {
            case AIOPMS_SCHEMA_FAQ:
                $main_entity = aiopms_generate_faq_schema($post_id);
                break;
            case AIOPMS_SCHEMA_BLOG:
                $main_entity = aiopms_generate_blog_schema($post_id);
                break;
            case AIOPMS_SCHEMA_ARTICLE:
                $main_entity = aiopms_generate_article_schema($post_id);
                break;
            case AIOPMS_SCHEMA_SERVICE:
                $main_entity = aiopms_generate_service_schema($post_id);
                break;
            case AIOPMS_SCHEMA_PRODUCT:
                $main_entity = aiopms_generate_product_schema($post_id);
                break;
            case AIOPMS_SCHEMA_ORGANIZATION:
                // If the page IS the organization page, merge distinct features or just use global org
                $main_entity = aiopms_generate_organization_schema($post_id);
                // Force ID match if specific org page
                $main_entity['@id'] = $site_url . '/#organization'; 
                break;
            case AIOPMS_SCHEMA_LOCAL_BUSINESS:
                $main_entity = aiopms_generate_local_business_schema($post_id);
                break;
            case AIOPMS_SCHEMA_HOWTO:
                $main_entity = aiopms_generate_howto_schema($post_id);
                break;
            case AIOPMS_SCHEMA_REVIEW:
                $main_entity = aiopms_generate_review_schema($post_id);
                break;
            case AIOPMS_SCHEMA_EVENT:
                $main_entity = aiopms_generate_event_schema($post_id);
                break;
            default:
                // For default webpages, the WebPage IS the main entity, so we don't add a separate node
                $main_entity = null;
                break;
        }

        if ($main_entity) {
            // Clean up Main Entity
            unset($main_entity['@context']); // Remove context as it's in root
            
            // Assign ID if not present
            if (!isset($main_entity['@id'])) {
                $main_entity['@id'] = $permalink . '#article'; // Default suffix, could vary
            }
            
            // Link to WebPage
            $main_entity['isPartOf'] = ['@id' => $permalink . '#webpage'];
            $main_entity['mainEntityOfPage'] = ['@id' => $permalink . '#webpage'];
            
            // Link to Publisher/Organization
            $main_entity['publisher'] = ['@id' => $site_url . '/#organization'];

            $graph[] = $main_entity;
            
            // Link WebPage to Main Entity
            $webpage_schema['about'] = ['@id' => $main_entity['@id']];
        }

        $graph[] = $webpage_schema;

        $schema_data = [
            '@context' => 'https://schema.org',
            '@graph' => $graph
        ];

        // Store schema data as post meta
        update_post_meta($post_id, '_aiopms_schema_type', $schema_type);
        update_post_meta($post_id, '_aiopms_schema_data', $schema_data);
        
        return $schema_data;
    }
}

// Generate WebSite Schema
function aiopms_generate_website_schema() {
    $site_url = home_url();
    $site_name = get_bloginfo('name');
    
    return [
        '@type' => 'WebSite',
        '@id' => $site_url . '/#website',
        'url' => $site_url,
        'name' => $site_name,
        'description' => get_bloginfo('description'),
        'publisher' => ['@id' => $site_url . '/#organization'],
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => $site_url . '/?s={search_term_string}',
            'query-input' => 'required name=search_term_string'
        ]
    ];
}


// Generate FAQ schema - Enhanced with AI extraction
function aiopms_generate_faq_schema($post_id) {
    $post = get_post($post_id);
    $content = $post->post_content;
    
    // Try AI extraction first for better accuracy
    $ai_data = aiopms_ai_extract_schema_data($post_id, 'faq');
    
    if ($ai_data && is_array($ai_data)) {
        $faq_items = [];
        foreach ($ai_data as $item) {
            if (isset($item['question']) && isset($item['answer']) && !empty($item['question']) && !empty($item['answer'])) {
                $faq_items[] = [
                    '@type' => 'Question',
                    'name' => sanitize_text_field($item['question']),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => sanitize_textarea_field($item['answer'])
                    ]
                ];
            }
        }
        if (!empty($faq_items)) {
            return [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faq_items
            ];
        }
    }

    // Fallback: Extract questions and answers from content using regex
    $faq_items = aiopms_extract_faq_items($content);
    
    if (empty($faq_items)) {
        // Fallback to webpage schema if no FAQ items found
        return aiopms_generate_webpage_schema($post_id);
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $faq_items
    ];

    return $schema;
}


// Extract FAQ items from content
function aiopms_extract_faq_items($content) {
    $faq_items = [];
    
    // Pattern to match question-answer pairs
    $patterns = [
        // Match headings with questions followed by paragraphs
        '/(<h[1-6][^>]*>.*\?.*<\/h[1-6]>)(.*?)(?=<h[1-6]|$)/is',
        // Match bold questions followed by text
        '/(<strong>.*\?.*<\/strong>)(.*?)(?=<strong>|$)/is',
        // Match paragraph with strong question
        '/(<p><strong>.*\?.*<\/strong><\/p>)(.*?)(?=<p><strong>|$)/is'
    ];

    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $question = sanitize_text_field(trim(strip_tags($match[1])));
                $answer = sanitize_textarea_field(trim(strip_tags($match[2])));
                
                if (!empty($question) && !empty($answer)) {
                    $faq_items[] = [
                        '@type' => 'Question',
                        'name' => $question,
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $answer
                        ]
                    ];
                }
            }
        }
    }

    return $faq_items;
}

// Generate Blog schema
function aiopms_generate_blog_schema($post_id) {
    $post = get_post($post_id);
    $author_id = $post->post_author;
    $author = get_userdata($author_id);
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => sanitize_text_field(get_the_title($post_id)),
        'description' => sanitize_text_field(get_the_excerpt($post_id)),
        'datePublished' => get_the_date('c', $post_id),
        'dateModified' => get_the_modified_date('c', $post_id),
        'author' => [
            '@type' => 'Person',
            'name' => sanitize_text_field($author->display_name)
        ],
        'publisher' => aiopms_get_organization_schema(),
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => esc_url_raw(get_permalink($post_id))
        ]
    ];

    // Add featured image if available
    $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
    if ($thumbnail_url) {
        $schema['image'] = [
            '@type' => 'ImageObject',
            'url' => $thumbnail_url
        ];
    }

    return $schema;
}

// Generate Article schema
function aiopms_generate_article_schema($post_id) {
    $post = get_post($post_id);
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => sanitize_text_field(get_the_title($post_id)),
        'description' => sanitize_text_field(get_the_excerpt($post_id)),
        'datePublished' => get_the_date('c', $post_id),
        'dateModified' => get_the_modified_date('c', $post_id),
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => esc_url_raw(get_permalink($post_id))
        ],
        'publisher' => aiopms_get_organization_schema()
    ];

    // Add author if available
    $author_id = $post->post_author;
    if ($author_id) {
        $author = get_userdata($author_id);
        $schema['author'] = [
            '@type' => 'Person',
            'name' => $author->display_name
        ];
    }

    // Add featured image if available
    $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
    if ($thumbnail_url) {
        $schema['image'] = [
            '@type' => 'ImageObject',
            'url' => $thumbnail_url
        ];
    }
    
    // Try AI extraction for richer data (Content Aware)
    $ai_data = aiopms_ai_extract_schema_data($post_id, 'article');
    if ($ai_data && is_array($ai_data)) {
        if (!empty($ai_data['articleSection'])) {
            $schema['articleSection'] = sanitize_text_field($ai_data['articleSection']);
        }
        if (!empty($ai_data['alternativeHeadline'])) {
            $schema['alternativeHeadline'] = sanitize_text_field($ai_data['alternativeHeadline']);
        }
        if (!empty($ai_data['keywords'])) {
            $keywords = is_array($ai_data['keywords']) ? implode(', ', $ai_data['keywords']) : $ai_data['keywords'];
            $schema['keywords'] = sanitize_text_field($keywords);
        }
        if (!empty($ai_data['wordCount'])) {
            $schema['wordCount'] = absint($ai_data['wordCount']);
        }
        if (!empty($ai_data['proficiencyLevel'])) {
            $schema['proficiencyLevel'] = sanitize_text_field($ai_data['proficiencyLevel']);
        }
    }

    return $schema;
}

// Generate Service schema - Enhanced with AI extraction
function aiopms_generate_service_schema($post_id) {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => sanitize_text_field(get_the_title($post_id)),
        'description' => sanitize_text_field(get_the_excerpt($post_id)),
        'provider' => aiopms_get_organization_schema(),
        'areaServed' => 'Worldwide',
        'serviceType' => sanitize_text_field(get_the_title($post_id))
    ];

    // Try AI extraction for richer data
    $ai_data = aiopms_ai_extract_schema_data($post_id, 'service');
    if ($ai_data && is_array($ai_data)) {
        if (!empty($ai_data['serviceType'])) {
            $schema['serviceType'] = sanitize_text_field($ai_data['serviceType']);
        }
        if (!empty($ai_data['areaServed'])) {
            $schema['areaServed'] = sanitize_text_field($ai_data['areaServed']);
        }
        if (!empty($ai_data['features']) && is_array($ai_data['features'])) {
            $schema['hasOfferCatalog'] = [
                '@type' => 'OfferCatalog',
                'name' => 'Service Features',
                'itemListElement' => array_map('sanitize_text_field', $ai_data['features'])
            ];
        }
        if (!empty($ai_data['offers']) && is_array($ai_data['offers'])) {
            $offer = [
                '@type' => 'Offer',
                'price' => sanitize_text_field($ai_data['offers']['price'] ?? '0'),
                'priceCurrency' => sanitize_text_field($ai_data['offers']['priceCurrency'] ?? 'USD')
            ];
            if (!empty($ai_data['offers']['description'])) {
                $offer['description'] = sanitize_text_field($ai_data['offers']['description']);
            }
            $schema['offers'] = $offer;
        }
        if (!empty($ai_data['audience'])) {
            $schema['audience'] = [
                '@type' => 'Audience',
                'audienceType' => sanitize_text_field($ai_data['audience'])
            ];
        }
        if (!empty($ai_data['provider'])) {
            $schema['provider'] = [
                '@type' => 'Organization',
                'name' => sanitize_text_field($ai_data['provider'])
            ];
        }
    }

    return $schema;
}

// Generate Product schema - Enhanced with AI extraction
function aiopms_generate_product_schema($post_id) {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => sanitize_text_field(get_the_title($post_id)),
        'description' => sanitize_text_field(get_the_excerpt($post_id)),
        'sku' => 'PROD-' . absint($post_id)
    ];

    // Try AI extraction for richer data
    $ai_data = aiopms_ai_extract_schema_data($post_id, 'product');
    if ($ai_data && is_array($ai_data)) {
        if (!empty($ai_data['brand'])) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => sanitize_text_field($ai_data['brand'])
            ];
        }
        if (!empty($ai_data['sku'])) {
            $schema['sku'] = sanitize_text_field($ai_data['sku']);
        }
        
        // Build offers
        $schema['offers'] = [
            '@type' => 'Offer',
            'priceCurrency' => sanitize_text_field($ai_data['priceCurrency'] ?? 'USD'),
            'price' => sanitize_text_field($ai_data['price'] ?? '0'),
            'availability' => 'https://schema.org/' . ($ai_data['availability'] === 'OutOfStock' ? 'OutOfStock' : 'InStock')
        ];
        
        // Add richer product details
        if (!empty($ai_data['color'])) {
            $schema['color'] = sanitize_text_field($ai_data['color']);
        }
        if (!empty($ai_data['material'])) {
            $schema['material'] = sanitize_text_field($ai_data['material']);
        }
        if (!empty($ai_data['condition'])) {
            $schema['itemCondition'] = 'https://schema.org/' . sanitize_text_field($ai_data['condition']);
        }
        if (!empty($ai_data['features']) && is_array($ai_data['features'])) {
            $schema['additionalProperty'] = [];
            foreach ($ai_data['features'] as $feature) {
                $schema['additionalProperty'][] = [
                    '@type' => 'PropertyValue',
                    'name' => 'Feature',
                    'value' => sanitize_text_field($feature)
                ];
            }
        }
    } else {
        // Fallback
        $schema['offers'] = [
            '@type' => 'Offer',
            'priceCurrency' => 'USD',
            'price' => '0.00',
            'availability' => 'https://schema.org/InStock'
        ];
    }

    // Add featured image if available
    $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
    if ($thumbnail_url) {
        $schema['image'] = $thumbnail_url;
    }

    return $schema;
}

// Generate Organization schema
function aiopms_generate_organization_schema($post_id) {
    return aiopms_get_organization_schema();
}

// Get organization schema (reusable) - Enhanced with Business Settings
// Get organization schema (reusable) - Enhanced with Business Settings
function aiopms_get_organization_schema() {
    // Use business settings if available, fallback to site info
    $business_name = get_option('aiopms_business_name', get_bloginfo('name'));
    $business_description = get_option('aiopms_business_description', get_bloginfo('description'));
    $site_url = home_url();
    
    $schema = [
        '@type' => 'Organization',
        '@id' => $site_url . '/#organization',
        'name' => $business_name,
        'url' => $site_url,
        'logo' => [
            '@type' => 'ImageObject',
            'url' => get_site_icon_url() ?: ''
        ]
    ];

    // Add description if available
    if (!empty($business_description)) {
        $schema['description'] = sanitize_text_field($business_description);
    }

    // Add contact information
    $email = get_option('aiopms_business_email', '');
    $phone = get_option('aiopms_business_phone', '');
    
    if (!empty($email) || !empty($phone)) {
        $schema['contactPoint'] = [
            '@type' => 'ContactPoint',
            'contactType' => 'customer service'
        ];
        if (!empty($email)) {
            $schema['contactPoint']['email'] = sanitize_email($email);
        }
        if (!empty($phone)) {
            $schema['contactPoint']['telephone'] = sanitize_text_field($phone);
        }
    }

    // Add address if available
    $address = get_option('aiopms_business_address', '');
    if (!empty($address)) {
        $schema['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => sanitize_text_field($address)
        ];
    }

    // Add social links if available
    $social_links = [];
    $facebook = get_option('aiopms_business_social_facebook', '');
    $twitter = get_option('aiopms_business_social_twitter', '');
    $linkedin = get_option('aiopms_business_social_linkedin', '');
    
    if (!empty($facebook)) $social_links[] = esc_url_raw($facebook);
    if (!empty($twitter)) $social_links[] = esc_url_raw($twitter);
    if (!empty($linkedin)) $social_links[] = esc_url_raw($linkedin);
    
    if (!empty($social_links)) {
        $schema['sameAs'] = $social_links;
    }

    return $schema;
}

// Generate BreadcrumbList schema
function aiopms_generate_breadcrumb_schema($post_id) {
    $breadcrumbs = [];
    $position = 1;
    $site_url = home_url();
    
    // Home
    $breadcrumbs[] = [
        '@type' => 'ListItem',
        'position' => $position++,
        'item' => [
            '@id' => $site_url,
            'name' => 'Home'
        ]
    ];

    $ancestors = get_post_ancestors($post_id);
    if ($ancestors) {
        $ancestors = array_reverse($ancestors);
        foreach ($ancestors as $ancestor_id) {
            $breadcrumbs[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'item' => [
                    '@id' => get_permalink($ancestor_id),
                    'name' => get_the_title($ancestor_id)
                ]
            ];
        }
    }

    // Current Page
    $breadcrumbs[] = [
        '@type' => 'ListItem',
        'position' => $position,
        'item' => [
            '@id' => get_permalink($post_id),
            'name' => get_the_title($post_id)
        ]
    ];

    return [
        '@type' => 'BreadcrumbList',
        '@id' => get_permalink($post_id) . '#breadcrumb',
        'itemListElement' => $breadcrumbs
    ];
}


// Generate Local Business schema - Enhanced with Business Settings
function aiopms_generate_local_business_schema($post_id) {
    $business_name = get_option('aiopms_business_name', get_bloginfo('name'));
    $business_description = get_option('aiopms_business_description', get_the_excerpt($post_id));
    $address = get_option('aiopms_business_address', '');
    $phone = get_option('aiopms_business_phone', '');
    $email = get_option('aiopms_business_email', '');
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => sanitize_text_field($business_name),
        'description' => sanitize_text_field($business_description),
        'url' => esc_url_raw(home_url())
    ];

    // Add address if available
    if (!empty($address)) {
        $schema['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => sanitize_text_field($address)
        ];
    }

    // Add contact info
    if (!empty($phone)) {
        $schema['telephone'] = sanitize_text_field($phone);
    }
    if (!empty($email)) {
        $schema['email'] = sanitize_email($email);
    }

    // Add logo
    $logo_url = get_site_icon_url();
    if ($logo_url) {
        $schema['logo'] = esc_url_raw($logo_url);
    }

    return $schema;
}


// Generate WebPage schema (fallback)
function aiopms_generate_webpage_schema($post_id) {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => sanitize_text_field(get_the_title($post_id)),
        'description' => sanitize_text_field(get_the_excerpt($post_id)),
        'url' => esc_url_raw(get_permalink($post_id))
    ];

    // Add publisher information
    $schema['publisher'] = aiopms_get_organization_schema();

    return $schema;
}

// Generate HowTo schema - Enhanced with AI extraction
function aiopms_generate_howto_schema($post_id) {
    $post = get_post($post_id);
    $content = $post->post_content;
    
    // Try AI extraction first for better step identification
    $ai_data = aiopms_ai_extract_schema_data($post_id, 'howto');
    
    if ($ai_data && is_array($ai_data) && !empty($ai_data['steps'])) {
        $steps = [];
        $step_counter = 1;
        foreach ($ai_data['steps'] as $step) {
            if (isset($step['text']) && !empty($step['text'])) {
                $steps[] = [
                    '@type' => 'HowToStep',
                    'name' => sanitize_text_field($step['name'] ?? "Step $step_counter"),
                    'text' => sanitize_textarea_field($step['text'])
                ];
                $step_counter++;
            }
        }
        
        if (!empty($steps)) {
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'HowTo',
                'name' => sanitize_text_field(get_the_title($post_id)),
                'description' => sanitize_text_field(get_the_excerpt($post_id)),
                'step' => $steps
            ];
            
            if (!empty($ai_data['totalTime'])) {
                $schema['totalTime'] = sanitize_text_field($ai_data['totalTime']);
            }
            if (!empty($ai_data['estimatedCost'])) {
                $schema['estimatedCost'] = [
                    '@type' => 'MonetaryAmount',
                    'currency' => 'USD',
                    'value' => sanitize_text_field($ai_data['estimatedCost'])
                ];
            }
            return $schema;
        }
    }

    // Fallback: Extract steps from content using regex
    $steps = aiopms_extract_howto_steps($content);
    
    if (empty($steps)) {
        return aiopms_generate_webpage_schema($post_id);
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'HowTo',
        'name' => sanitize_text_field(get_the_title($post_id)),
        'description' => sanitize_text_field(get_the_excerpt($post_id)),
        'step' => $steps
    ];

    $total_time = aiopms_extract_total_time($content);
    if ($total_time) {
        $schema['totalTime'] = $total_time;
    }

    $estimated_cost = aiopms_extract_estimated_cost($content);
    if ($estimated_cost) {
        $schema['estimatedCost'] = [
            '@type' => 'MonetaryAmount',
            'currency' => 'USD',
            'value' => $estimated_cost
        ];
    }

    return $schema;
}


// Extract HowTo steps from content
function aiopms_extract_howto_steps($content) {
    $steps = [];
    
    // Pattern to match numbered steps
    $patterns = [
        // Match "Step 1:", "1.", etc.
        '/(?:step\s+)?(\d+)\.?\s*([^<]+?)(?=(?:step\s+)?\d+\.|$)/is',
        // Match ordered list items
        '/<li[^>]*>([^<]+)<\/li>/is'
    ];

    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $step_text = sanitize_text_field(trim(strip_tags($match[2] ?? $match[1])));
                if (!empty($step_text) && strlen($step_text) > 10) {
                    $steps[] = [
                        '@type' => 'HowToStep',
                        'name' => 'Step ' . count($steps) + 1,
                        'text' => $step_text
                    ];
                }
            }
        }
    }

    return $steps;
}

// Extract total time from content
function aiopms_extract_total_time($content) {
    $time_patterns = [
        '/(\d+)\s*(?:minutes?|mins?)/i',
        '/(\d+)\s*(?:hours?|hrs?)/i',
        '/(\d+)\s*(?:days?)/i'
    ];

    foreach ($time_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $value = intval($matches[1]);
            if (stripos($matches[0], 'minute') !== false || stripos($matches[0], 'min') !== false) {
                return 'PT' . $value . 'M';
            } elseif (stripos($matches[0], 'hour') !== false || stripos($matches[0], 'hr') !== false) {
                return 'PT' . $value . 'H';
            } elseif (stripos($matches[0], 'day') !== false) {
                return 'P' . $value . 'D';
            }
        }
    }

    return null;
}

// Extract estimated cost from content
function aiopms_extract_estimated_cost($content) {
    $cost_patterns = [
        '/\$(\d+(?:\.\d{2})?)/',
        '/(\d+(?:\.\d{2})?)\s*(?:dollars?|usd)/i'
    ];

    foreach ($cost_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            return floatval($matches[1]);
        }
    }

    return null;
}

// Generate Review schema - Enhanced with AI extraction
function aiopms_generate_review_schema($post_id) {
    $post = get_post($post_id);
    $content = $post->post_content;
    
    // Try AI extraction first
    $ai_data = aiopms_ai_extract_schema_data($post_id, 'review');
    
    $rating = null;
    $reviewed_item = null;
    
    if ($ai_data && is_array($ai_data)) {
        $rating = isset($ai_data['ratingValue']) ? floatval($ai_data['ratingValue']) : null;
        $reviewed_item = !empty($ai_data['itemReviewed']) ? sanitize_text_field($ai_data['itemReviewed']) : null;
    }
    
    // Fallback to regex if AI failed
    if (!$rating) {
        $rating = aiopms_extract_review_rating($content);
    }
    if (!$reviewed_item) {
        $reviewed_item = aiopms_extract_reviewed_item($post->post_title, $content);
    }
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Review',
        'headline' => sanitize_text_field(get_the_title($post_id)),
        'reviewBody' => sanitize_text_field(wp_strip_all_tags(wp_trim_words($content, 200))),
        'datePublished' => get_the_date('c', $post_id),
        'author' => [
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', $post->post_author)
        ]
    ];

    if ($rating) {
        $schema['reviewRating'] = [
            '@type' => 'Rating',
            'ratingValue' => $rating,
            'bestRating' => 5
        ];
    }

    if ($reviewed_item) {
        $schema['itemReviewed'] = [
            '@type' => 'Thing',
            'name' => $reviewed_item
        ];
    }

    return $schema;
}


// Extract review rating from content
function aiopms_extract_review_rating($content) {
    $rating_patterns = [
        '/(\d+)\/(\d+)\s*(?:stars?|rating)/i',
        '/(\d+)\s*(?:out\s*of\s*)?(\d+)/i',
        '/rating[:\s]*(\d+)/i'
    ];

    foreach ($rating_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $rating = floatval($matches[1]);
            $max_rating = isset($matches[2]) ? floatval($matches[2]) : 5;
            
            // Normalize to 5-star scale if needed
            if ($max_rating != 5) {
                $rating = ($rating / $max_rating) * 5;
            }
            
            return round($rating, 1);
        }
    }

    return null;
}

// Extract reviewed item from title and content
function aiopms_extract_reviewed_item($title, $content) {
    // Try to extract from title first
    $title_patterns = [
        '/review[:\s]*of\s*([^,]+)/i',
        '/([^,]+)\s*review/i'
    ];

    foreach ($title_patterns as $pattern) {
        if (preg_match($pattern, $title, $matches)) {
            return sanitize_text_field(trim($matches[1]));
        }
    }

    // Fallback: look for product names in content
    $content_patterns = [
        '/product[:\s]*([^,\.]+)/i',
        '/service[:\s]*([^,\.]+)/i'
    ];

    foreach ($content_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            return sanitize_text_field(trim($matches[1]));
        }
    }

    return null;
}

// Generate Event schema
function aiopms_generate_event_schema($post_id) {
    $post = get_post($post_id);
    $content = $post->post_content;
    
    // Extract event data
    $event_date = aiopms_extract_event_date($content);
    $event_location = aiopms_extract_event_location($content);
    $event_organizer = aiopms_extract_event_organizer($content);
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        'name' => sanitize_text_field(get_the_title($post_id)),
        'description' => sanitize_text_field(get_the_excerpt($post_id))
    ];

    if ($event_date) {
        $schema['startDate'] = $event_date;
    }

    if ($event_location) {
        $schema['location'] = [
            '@type' => 'Place',
            'name' => $event_location
        ];
    }

    if ($event_organizer) {
        $schema['organizer'] = [
            '@type' => 'Organization',
            'name' => $event_organizer
        ];
    } else {
        $schema['organizer'] = aiopms_get_organization_schema();
    }

    return $schema;
}

// Extract event date from content
function aiopms_extract_event_date($content) {
    $date_patterns = [
        '/(\d{1,2}\/\d{1,2}\/\d{4})/',
        '/(\d{4}-\d{2}-\d{2})/',
        '/(\w+\s+\d{1,2},?\s+\d{4})/'
    ];

    foreach ($date_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            $date = strtotime($matches[1]);
            if ($date !== false) {
                return date('c', $date);
            }
        }
    }

    return null;
}

// Extract event location from content
function aiopms_extract_event_location($content) {
    $location_patterns = [
        '/location[:\s]*([^,\.]+)/i',
        '/venue[:\s]*([^,\.]+)/i',
        '/address[:\s]*([^,\.]+)/i'
    ];

    foreach ($location_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            return sanitize_text_field(trim($matches[1]));
        }
    }

    return null;
}

// Extract event organizer from content
function aiopms_extract_event_organizer($content) {
    $organizer_patterns = [
        '/organizer[:\s]*([^,\.]+)/i',
        '/hosted\s*by[:\s]*([^,\.]+)/i',
        '/presented\s*by[:\s]*([^,\.]+)/i'
    ];

    foreach ($organizer_patterns as $pattern) {
        if (preg_match($pattern, $content, $matches)) {
            return sanitize_text_field(trim($matches[1]));
        }
    }

    return null;
}

/**
 * Output schema markup in wp_head.
 * 
 * @since 3.0
 */
function aiopms_output_schema_markup() {
    if (!is_singular()) {
        return;
    }
    
    // Get schema data from post meta
    $schema_data = get_post_meta(get_the_ID(), '_aiopms_schema_data', true);
    
    if (!empty($schema_data)) {
        echo "\n" . '<!-- AIOPMS Schema -->' . "\n";
        echo '<script type="application/ld+json">' . "\n";
        
        if (is_array($schema_data) || is_object($schema_data)) {
             echo wp_json_encode($schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
             echo $schema_data;
        }
        
        echo "\n" . '</script>' . "\n";
        echo '<!-- /AIOPMS Schema -->' . "\n";
    }
}
add_action('wp_head', 'aiopms_output_schema_markup');

// Generate schema when page is saved
function aiopms_generate_schema_on_save($post_id) {
    // Check if this is an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Check if schema generation is enabled
    $auto_generate = get_option('aiopms_auto_schema_generation', true);
    if (!$auto_generate) {
        return;
    }

    // Generate schema markup
    aiopms_generate_schema_markup($post_id);
}
add_action('save_post', 'aiopms_generate_schema_on_save');
add_action('save_post_page', 'aiopms_generate_schema_on_save');

// Add schema column to pages list
function aiopms_add_schema_column($columns) {
    $columns['schema'] = 'Schema';
    return $columns;
}
add_filter('manage_page_posts_columns', 'aiopms_add_schema_column');

// Display schema type in the schema column
function aiopms_display_schema_column($column, $post_id) {
    if ($column === 'schema') {
        $schema_type = get_post_meta($post_id, '_aiopms_schema_type', true);
        if (!empty($schema_type)) {
            echo '<span class="aiopms-schema-badge aiopms-schema-' . esc_attr($schema_type) . '">' . esc_html(ucfirst($schema_type)) . '</span>';
        } else {
            echo '<span class="aiopms-schema-badge aiopms-schema-none">Not Generated</span>';
        }
    }
}
add_action('manage_page_posts_custom_column', 'aiopms_display_schema_column', 10, 2);

// Make schema column sortable
function aiopms_make_schema_column_sortable($columns) {
    $columns['schema'] = 'schema';
    return $columns;
}
add_filter('manage_edit-page_sortable_columns', 'aiopms_make_schema_column_sortable');

// Register admin hooks for Dynamic CPTs
function aiopms_register_cpt_schema_admin_hooks() {
    $dynamic_cpts = get_option('aiopms_dynamic_cpts', []);
    foreach ($dynamic_cpts as $post_type => $data) {
        add_filter("manage_{$post_type}_posts_columns", 'aiopms_add_schema_column');
        add_action("manage_{$post_type}_posts_custom_column", 'aiopms_display_schema_column', 10, 2);
        add_filter("manage_edit-{$post_type}_sortable_columns", 'aiopms_make_schema_column_sortable');
    }
    // Also add row actions for all post types (not just pages)
    add_filter('post_row_actions', 'aiopms_add_schema_quick_actions', 10, 2);
}
add_action('admin_init', 'aiopms_register_cpt_schema_admin_hooks');

// Handle schema column sorting
function aiopms_handle_schema_column_sorting($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->get('orderby') === 'schema') {
        $query->set('meta_key', '_aiopms_schema_type');
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'aiopms_handle_schema_column_sorting');

// Add quick actions for schema generation
function aiopms_add_schema_quick_actions($actions, $post) {
    $schema_type = get_post_meta($post->ID, '_aiopms_schema_type', true);

    if (empty($schema_type)) {
        $actions['generate_schema'] = '<a href="' . wp_nonce_url(admin_url('admin.php?page=aiopms-page-management&action=generate_schema&post=' . $post->ID), 'generate_schema_' . $post->ID) . '">Generate Schema</a>';
    } else {
        $actions['regenerate_schema'] = '<a href="' . wp_nonce_url(admin_url('admin.php?page=aiopms-page-management&action=regenerate_schema&post=' . $post->ID), 'regenerate_schema_' . $post->ID) . '">Regenerate Schema</a>';
        $actions['remove_schema'] = '<a href="' . wp_nonce_url(admin_url('admin.php?page=aiopms-page-management&action=remove_schema&post=' . $post->ID), 'remove_schema_' . $post->ID) . '" onclick="return confirm(\'Are you sure you want to remove schema from this page?\')">Remove Schema</a>';
    }

    return $actions;
}
add_filter('page_row_actions', 'aiopms_add_schema_quick_actions', 10, 2);

// Handle schema generation actions
function aiopms_handle_schema_generation_actions() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'aiopms-page-management') {
        return;
    }

    if (isset($_GET['action']) && isset($_GET['post']) && isset($_GET['_wpnonce'])) {
        $action = sanitize_text_field($_GET['action']);
        $post_id = intval($_GET['post']);

        if ($action === 'generate_schema') {
            if (wp_verify_nonce($_GET['_wpnonce'], 'generate_schema_' . $post_id)) {
                aiopms_generate_schema_markup($post_id);
                $post_type = get_post_type($post_id);
                wp_redirect(admin_url('edit.php?post_type=' . $post_type . '&schema_generated=1'));
                exit;
            }
        } elseif ($action === 'regenerate_schema') {
            if (wp_verify_nonce($_GET['_wpnonce'], 'regenerate_schema_' . $post_id)) {
                aiopms_generate_schema_markup($post_id);
                $post_type = get_post_type($post_id);
                wp_redirect(admin_url('edit.php?post_type=' . $post_type . '&schema_regenerated=1'));
                exit;
            }
        }
    }
}
add_action('admin_init', 'aiopms_handle_schema_generation_actions');

// Add admin notices for schema generation
function aiopms_schema_generation_notices() {
    if (isset($_GET['schema_generated']) && sanitize_key($_GET['schema_generated']) == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Schema generated successfully!', 'aiopms') . '</p></div>';
    }
    if (isset($_GET['schema_regenerated']) && sanitize_key($_GET['schema_regenerated']) == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Schema regenerated successfully!', 'aiopms') . '</p></div>';
    }
}
add_action('admin_notices', 'aiopms_schema_generation_notices');

// Remove schema from a page
function aiopms_remove_schema_from_page($post_id) {
    delete_post_meta($post_id, '_aiopms_schema_type');
    delete_post_meta($post_id, '_aiopms_schema_data');
    return true;
}

// Handle schema removal actions
function aiopms_handle_schema_removal_actions() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'aiopms-page-management') {
        return;
    }

    if (isset($_GET['action']) && isset($_GET['post']) && isset($_GET['_wpnonce'])) {
        $action = sanitize_text_field($_GET['action']);
        $post_id = intval($_GET['post']);

        if ($action === 'remove_schema') {
            if (wp_verify_nonce($_GET['_wpnonce'], 'remove_schema_' . $post_id)) {
                aiopms_remove_schema_from_page($post_id);
                $post_type = get_post_type($post_id);
                wp_redirect(admin_url('edit.php?post_type=' . $post_type . '&schema_removed=1'));
                exit;
            }
        }
    }
}
add_action('admin_init', 'aiopms_handle_schema_removal_actions');

// Add admin notices for schema removal
function aiopms_schema_removal_notices() {
    if (isset($_GET['schema_removed']) && sanitize_key($_GET['schema_removed']) == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Schema removed successfully!', 'aiopms') . '</p></div>';
    }
}
add_action('admin_notices', 'aiopms_schema_removal_notices');

// Schema generator tab content (enhanced with management dashboard)
function aiopms_schema_generator_tab() {
    // Use the enhanced management dashboard
    aiopms_schema_management_dashboard();
}

// Enhanced schema management dashboard
function aiopms_schema_management_dashboard() {
    // Handle bulk actions
    if (isset($_POST['bulk_schema_action']) && check_admin_referer('aiopms_bulk_schema_action')) {
        $action = sanitize_text_field($_POST['bulk_schema_action']);
        $selected_pages = isset($_POST['selected_pages']) ? array_map('intval', $_POST['selected_pages']) : [];
        
        if (!empty($selected_pages)) {
            $processed = 0;
            foreach ($selected_pages as $page_id) {
                if ($action === 'generate') {
                    aiopms_generate_schema_markup($page_id);
                    $processed++;
                } elseif ($action === 'remove') {
                    aiopms_remove_schema_from_page($page_id);
                    $processed++;
                }
            }
            
            $message = sprintf(
                esc_html__('Processed %d pages successfully!', 'aiopms'),
                $processed
            );
            echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
        }
    }

    // Get all pages with schema information
    $pages = get_posts([
        'post_type' => 'page',
        'numberposts' => -1,
        'post_status' => 'any',
        'orderby' => 'title',
        'order' => 'ASC'
    ]);

    $schema_stats = [
        'total' => 0,
        'with_schema' => 0,
        'types' => []
    ];

    foreach ($pages as $page) {
        $schema_stats['total']++;
        $schema_type = get_post_meta($page->ID, '_aiopms_schema_type', true);
        if (!empty($schema_type)) {
            $schema_stats['with_schema']++;
            if (!isset($schema_stats['types'][$schema_type])) {
                $schema_stats['types'][$schema_type] = 0;
            }
            $schema_stats['types'][$schema_type]++;
        }
    }
    ?>
    <div class="wrap aiopms-schema-dashboard">
        <p>Manage structured data (schema.org) markup for your pages to improve SEO and search visibility.</p>
        
        <!-- Schema Statistics -->
        <div class="aiopms-schema-stats">
            <h2>Schema Statistics</h2>
            <div class="aiopms-stats-grid">
                <div class="aiopms-stat-card">
                    <h3><?php echo esc_html($schema_stats['total']); ?></h3>
                    <p>Total Pages</p>
                </div>
                <div class="aiopms-stat-card">
                    <h3><?php echo esc_html($schema_stats['with_schema']); ?></h3>
                    <p>Pages with Schema</p>
                </div>
                <div class="aiopms-stat-card">
                    <h3><?php echo esc_html($schema_stats['total'] - $schema_stats['with_schema']); ?></h3>
                    <p>Pages without Schema</p>
                </div>
                <div class="aiopms-stat-card">
                    <h3><?php echo esc_html($schema_stats['total'] > 0 ? round(($schema_stats['with_schema'] / $schema_stats['total']) * 100, 1) : 0); ?>%</h3>
                    <p>Schema Coverage</p>
                </div>
            </div>
            
            <?php if (!empty($schema_stats['types'])): ?>
            <h3>Schema Type Distribution</h3>
            <div class="aiopms-schema-types">
                <?php foreach ($schema_stats['types'] as $type => $count): ?>
                <div class="aiopms-schema-type">
                    <span class="aiopms-schema-badge aiopms-schema-<?php echo esc_attr($type); ?>">
                        <?php echo esc_html(ucfirst($type)); ?>
                    </span>
                    <span class="aiopms-schema-count"><?php echo $count; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Bulk Actions -->
        <div class="aiopms-bulk-actions">
            <h2>Bulk Actions</h2>
            <form method="post" action="">
                <?php wp_nonce_field('aiopms_bulk_schema_action'); ?>
                <div class="aiopms-bulk-controls">
                    <select name="bulk_schema_action" required>
                        <option value="">Select Action...</option>
                        <option value="generate">Generate Schema for Selected Pages</option>
                        <option value="remove">Remove Schema from Selected Pages</option>
                    </select>
                    <button type="submit" class="button button-primary">Apply to Selected</button>
                </div>
                
                <!-- Pages Table -->
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="select-all-pages">
                            </td>
                            <th class="manage-column">Page Title</th>
                            <th class="manage-column">Status</th>
                            <th class="manage-column">Schema Type</th>
                            <th class="manage-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                        <?php 
                        $schema_type = get_post_meta($page->ID, '_aiopms_schema_type', true);
                        $schema_data = get_post_meta($page->ID, '_aiopms_schema_data', true);
                        ?>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" name="selected_pages[]" value="<?php echo $page->ID; ?>">
                            </th>
                            <td>
                                <strong>
                                    <a href="<?php echo get_edit_post_link($page->ID); ?>">
                                        <?php echo esc_html($page->post_title); ?>
                                    </a>
                                </strong>
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="<?php echo get_permalink($page->ID); ?>" target="_blank">View</a> |
                                    </span>
                                    <span class="edit">
                                        <a href="<?php echo get_edit_post_link($page->ID); ?>">Edit</a>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="aiopms-page-status status-<?php echo esc_attr($page->post_status); ?>">
                                    <?php echo esc_html(ucfirst($page->post_status)); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($schema_type)): ?>
                                    <span class="aiopms-schema-badge aiopms-schema-<?php echo esc_attr($schema_type); ?>">
                                        <?php echo esc_html(ucfirst($schema_type)); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="aiopms-schema-badge aiopms-schema-none">No Schema</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="aiopms-schema-actions">
                                    <?php if (empty($schema_type)): ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aiopms-page-management&action=generate_schema&post=' . $page->ID), 'generate_schema_' . $page->ID); ?>" 
                                           class="button button-small">Generate Schema</a>
                                    <?php else: ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aiopms-page-management&action=regenerate_schema&post=' . $page->ID), 'regenerate_schema_' . $page->ID); ?>" 
                                           class="button button-small">Regenerate</a>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aiopms-page-management&action=remove_schema&post=' . $page->ID), 'remove_schema_' . $page->ID); ?>" 
                                           class="button button-small button-link-delete" 
                                           onclick="return confirm('Are you sure you want to remove schema from this page?')">Remove</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>

        <!-- Schema Information -->
        <div class="aiopms-schema-info">
            <h2>About Schema Markup</h2>
            <div class="aiopms-info-grid">
                <div class="aiopms-info-card">
                    <h3>What is Schema Markup?</h3>
                    <p>Schema.org markup helps search engines understand your content better, which can lead to rich snippets in search results and improved click-through rates.</p>
                </div>
                <div class="aiopms-info-card">
                    <h3>Where is Schema Inserted?</h3>
                    <p>Schema markup is automatically inserted in the <code>&lt;head&gt;</code> section of your pages as JSON-LD structured data. It's invisible to visitors but visible to search engines.</p>
                </div>
                <div class="aiopms-info-card">
                    <h3>Manual Removal</h3>
                    <p>To manually remove schema from a page, edit the page and remove the <code>_aiopms_schema_type</code> and <code>_aiopms_schema_data</code> custom fields.</p>
                </div>
                <div class="aiopms-info-card">
                    <h3>AI-Powered Detection</h3>
                    <p>The plugin uses AI to analyze your content and automatically determine the most appropriate schema type for each page, with fallback to keyword-based detection.</p>
                </div>
            </div>
        </div>
    </div>


    <?php
}

// AJAX handler for schema preview
function aiopms_ajax_get_schema_preview() {
    check_ajax_referer('aiopms_schema_preview', 'nonce');

    if (!current_user_can('edit_pages')) {
        wp_send_json_error(['message' => esc_html__('Unauthorized', 'aiopms')], 403);
    }

    $page_id = isset($_POST['page_id']) ? absint($_POST['page_id']) : 0;
    if (!$page_id) {
        wp_send_json_error(['message' => esc_html__('Invalid page ID', 'aiopms')]);
    }

    $schema_data = get_post_meta($page_id, '_aiopms_schema_data', true);
    if (empty($schema_data)) {
        wp_send_json_error('No schema data found');
    }

    wp_send_json_success($schema_data);
}
add_action('wp_ajax_aiopms_get_schema_preview', 'aiopms_ajax_get_schema_preview');
