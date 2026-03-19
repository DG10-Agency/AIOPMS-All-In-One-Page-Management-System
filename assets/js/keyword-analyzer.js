jQuery(document).ready(function ($) {
    let analysisData = null;
    let hasExpanded = false;

    // Load pages on page load
    loadPages();

    // Keyword count display
    $('#artitechcore_keywords_input').on('input', function () {
        const text = $(this).val();
        const keywords = text.split(/[\r\n,]+/).filter(k => k.trim().length > 0);
        $('#keyword-count').text('Keywords: ' + keywords.length);
        
        // Reset expansion state if input changes significantly
        if (hasExpanded && keywords.length > 0) {
            // Optional: could warn user here
        }
    });

    // AI Superpowers Toggle Logic
    $('#artitechcore_ai_superpowers').on('change', function() {
        const isAi = $(this).is(':checked');
        const analyzeBtn = $('#artitechcore-analyze-btn');
        const expandBtn = $('#artitechcore-expand-btn');
        
        if (isAi && !hasExpanded) {
            analyzeBtn.prop('disabled', true).addClass('btn-disabled');
            expandBtn.removeClass('dg10-hidden');
        } else {
            analyzeBtn.prop('disabled', false).removeClass('btn-disabled');
        }
    });

    // AI Expansion Trigger
    $('#artitechcore-expand-btn').on('click', function(e) {
        e.preventDefault();
        const btn = $(this);
        const spinner = btn.find('.dg10-spinner');
        const keywords = $('#artitechcore_keywords_input').val().trim();
        const pageId = $('#artitechcore_page_select').val();

        if (!keywords || !pageId) {
            alert('Please select a page and enter seed keywords first.');
            return;
        }

        btn.prop('disabled', true);
        spinner.removeClass('dg10-hidden');

        $.ajax({
            url: artitechcore_keyword_data.ajaxurl,
            type: 'POST',
            data: {
                action: 'artitechcore_ai_expand_keywords',
                page_id: pageId,
                keywords: keywords,
                nonce: artitechcore_keyword_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderEditableClusters(response.data.clusters);
                    hasExpanded = true;
                    $('#artitechcore-analyze-btn').prop('disabled', false).removeClass('btn-disabled');
                    
                    // Scroll to preview
                    $('html, body').animate({
                        scrollTop: $("#ai-expansion-preview").offset().top - 100
                    }, 500);
                } else {
                    alert('AI Expansion failed: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('AI Expansion connection error.');
            },
            complete: function() {
                btn.prop('disabled', false);
                spinner.addClass('dg10-hidden');
            }
        });
    });

    // Form submission for Analysis
    $('#artitechcore-keyword-analysis-form').on('submit', function (e) {
        e.preventDefault();
        const btn = $('#artitechcore-analyze-btn');
        const btnText = btn.find('.btn-text');
        const spinner = btn.find('.dg10-spinner');
        
        const pageId = $('#artitechcore_page_select').val();
        const isAi = $('#artitechcore_ai_superpowers').is(':checked');
        
        if (!pageId) {
            alert('Please select a page or post to analyze.');
            return;
        }

        let payload = {};
        
        if (isAi) {
            const clusters = collectCurrentClusters();
            if (clusters.length === 0) {
                alert('Please click "Expand with AI" first to review the semantic variations.');
                return;
            }
            payload = { clusters: clusters };
        } else {
            payload = $('#artitechcore_keywords_input').val();
            if (!payload || !payload.trim()) {
                alert('Please enter keywords for analysis.');
                return;
            }
        }

        // Show loading state
        btn.prop('disabled', true);
        btnText.addClass('dg10-hidden');
        spinner.removeClass('dg10-hidden');

        $.ajax({
            url: artitechcore_keyword_data.ajaxurl,
            type: 'POST',
            data: {
                action: 'artitechcore_analyze_keywords',
                page_id: pageId,
                keywords: payload,
                ai_superpowers: isAi, // explicitly use what PHP expects but also handle is_ai in PHP
                is_ai: isAi,
                nonce: artitechcore_keyword_data.nonce
            },
            success: function (response) {
                if (response.success) {
                    analysisData = response.data;
                    displayResults(response.data, isAi);
                    $('#artitechcore-analysis-results').removeClass('dg10-hidden');
                    // Scroll to results
                    $('html, body').animate({
                        scrollTop: $("#artitechcore-analysis-results").offset().top - 100
                    }, 500);
                } else {
                    alert('Analysis failed: ' + (response.data || 'Unknown error'));
                }
            },
            error: function (xhr, status, error) {
                console.error('Analysis error:', error);
                alert('Analysis failed. Please try again.');
            },
            complete: function () {
                btn.prop('disabled', false);
                btnText.removeClass('dg10-hidden');
                spinner.addClass('dg10-hidden');
            }
        });
    });

    function renderEditableClusters(clusters) {
        const container = $('#editable-clusters-container');
        container.empty();
        
        clusters.forEach((cluster, clusterIndex) => {
            const card = $(`
                <div class="dg10-cluster-card" data-cluster-index="${clusterIndex}">
                    <div class="cluster-seed">
                        <span class="seed-icon">🎯</span>
                        <span class="seed-text">${cluster.seed}</span>
                        <button type="button" class="dg10-add-variation-btn" title="Add Variation">+</button>
                    </div>
                    <div class="cluster-variations"></div>
                </div>
            `);
            
            const variationsContainer = card.find('.cluster-variations');
            cluster.variations.forEach((variation, varIndex) => {
                addVariationInput(variationsContainer, variation, varIndex);
            });
            
            container.append(card);
        });
        
        $('#ai-expansion-preview').removeClass('dg10-hidden');

        // Bind Add Variation buttons
        $('.dg10-add-variation-btn').off('click').on('click', function() {
            const container = $(this).closest('.dg10-cluster-card').find('.cluster-variations');
            addVariationInput(container, '', Date.now());
        });
    }

    function addVariationInput(container, value, index) {
        const inputWrapper = $(`
            <div class="variation-input-wrapper">
                <input type="text" 
                    class="variation-input" 
                    value="${value}" 
                    placeholder="Add variation..."
                >
                <button type="button" class="dg10-remove-var-btn">&times;</button>
            </div>
        `);
        
        inputWrapper.find('.dg10-remove-var-btn').on('click', function() {
            $(this).parent().remove();
        });
        
        container.append(inputWrapper);
    }

    function collectCurrentClusters() {
        const clusters = [];
        $('.dg10-cluster-card').each(function() {
            const card = $(this);
            const seed = card.find('.seed-text').text();
            const variations = [];
            card.find('.variation-input').each(function() {
                const val = $(this).val().trim();
                if (val) variations.push(val);
            });
            clusters.push({ seed, variations });
        });
        return clusters;
    }

    function loadPages() {
        $.ajax({
            url: artitechcore_keyword_data.ajaxurl,
            type: 'POST',
            data: {
                action: 'artitechcore_get_pages',
                nonce: artitechcore_keyword_data.nonce
            },
            success: function (response) {
                if (response.success) {
                    const select = $('#artitechcore_page_select');
                    select.empty();
                    select.append('<option value="">Select a page...</option>');

                    response.data.forEach(function (page) {
                        const option = $('<option></option>')
                            .attr('value', page.id)
                            .text(`${page.title} (${page.type})`);
                        select.append(option);
                    });
                }
            }
        });
    }

    function displayResults(data, isAi = false) {
        displayPageInfo(data.page_info);
        displaySummary(data.summary, isAi);
        
        if (isAi && data.summary.cluster_results) {
            displayTopicCards(data.summary.cluster_results);
            $('#topic-cards-section').removeClass('dg10-hidden');
        } else {
            $('#topic-cards-section').addClass('dg10-hidden');
        }

        displayKeywordsTable(data.keywords);
        displayRecommendations(data.summary.recommendations);
    }

    function displayPageInfo(pageInfo) {
        const container = $('#page-info-content');
        container.empty();
        const grid = $('<div class="page-info-grid"></div>');
        const infoItems = [
            { label: 'Page Title', value: pageInfo.title },
            { label: 'URL', value: $('<a></a>').attr({ 'href': pageInfo.url, 'target': '_blank' }).text('View Page') },
            { label: 'Word Count', value: pageInfo.word_count.toLocaleString() },
            { label: 'Content Size', value: (pageInfo.content_size / 1024).toFixed(1) + ' KB' },
            { label: 'Memory Used', value: pageInfo.memory_used + ' MB' },
            { label: 'Analysis Date', value: pageInfo.analysis_date }
        ];
        infoItems.forEach(item => {
            const el = $('<div class="page-info-item"></div>');
            el.append($('<div class="page-info-label"></div>').text(item.label));
            const val = $('<div class="page-info-value"></div>');
            if (typeof item.value === 'string' || typeof item.value === 'number') val.text(item.value);
            else val.append(item.value);
            el.append(val);
            grid.append(el);
        });
        container.append(grid);
    }

    function displaySummary(summary, isAi = false) {
        const container = $('#summary-content');
        container.empty();
        const intentContainer = $('#ai-intent-badge-container');
        if (isAi && summary.intent) {
            intentContainer.empty().removeClass('dg10-hidden');
            intentContainer.append($('<div class="dg10-badge dg10-badge-primary"></div>')
                .css({ 'padding': '8px 15px', 'font-size': '1.1em' })
                .html(`<strong>Detected Intent:</strong> ${summary.intent}`)); 
        } else {
            intentContainer.addClass('dg10-hidden');
        }
        const seoScoreClass = summary.seo_score >= 80 ? 'seo-excellent' :
            summary.seo_score >= 60 ? 'seo-good' :
                summary.seo_score >= 40 ? 'seo-fair' : 'seo-poor';
        const summaryGrid = $('<div class="summary-grid"></div>');
        const summaryItems = [
            { label: 'Total Keywords', value: summary.total_keywords },
            { label: 'Keywords Found', value: summary.keywords_found },
            { label: 'Avg Density', value: summary.average_density + '%' },
            { label: 'Avg Relevance', value: summary.average_relevance || 'N/A' },
            { label: 'SEO Score', value: summary.seo_score || 'N/A', extraClass: seoScoreClass },
            { label: 'Total Words', value: summary.total_words.toLocaleString() }
        ];
        summaryItems.forEach(item => {
            const el = $('<div class="summary-item"></div>');
            if (item.label === 'SEO Score') el.addClass('seo-score-item');
            const num = $('<div class="summary-number"></div>').text(item.value);
            if (item.extraClass) num.addClass(item.extraClass);
            el.append(num).append($('<div class="summary-label"></div>').text(item.label));
            summaryGrid.append(el);
        });
        container.append(summaryGrid);
    }

    function displayTopicCards(clusters) {
        const container = $('#topic-cards-container');
        container.empty();
        clusters.forEach(cluster => {
            const card = $('<div class="topic-card"></div>');
            if (!cluster.is_healthy) card.addClass('topic-card-low');
            if (cluster.is_stuffed) card.addClass('topic-card-warning');
            const header = $('<div class="topic-card-header"></div>');
            header.append($('<h4 class="topic-seed"></h4>').text(cluster.seed));
            header.append($('<div class="topic-stat"></div>').html(`<strong>${cluster.coverage_percent}%</strong> Coverage`));
            const body = $('<div class="topic-card-body"></div>');
            body.append($('<div class="topic-count-pill"></div>').text(`${cluster.total_count} Mentions`));
            const badges = $('<div class="topic-badges"></div>');
            badges.append(cluster.is_healthy ? '<span class="dg10-badge dg10-badge-success">Healthy Mix</span>' : '<span class="dg10-badge dg10-badge-warning">Low Variety</span>');
            if (cluster.is_stuffed) badges.append('<span class="dg10-badge dg10-badge-danger">Stuffing Risk</span>');
            body.append(badges);
            const varList = $('<div class="variation-preview"></div>');
            Object.entries(cluster.variation_data).forEach(([varName, count]) => {
                const dot = $('<span class="var-dot"></span>');
                if (count > 0) dot.addClass('var-found').attr('title', `${varName}: ${count} times`);
                else dot.attr('title', `${varName}: Not found`);
                varList.append(dot);
            });
            body.append(varList).append(header).append(body);
            container.append(card);
        });
    }

    function displayKeywordsTable(keywords) {
        const tbody = $('#keywords-table tbody');
        tbody.empty();
        keywords.forEach(function (keyword) {
            const areas = [];
            if (keyword.area_counts.title > 0) areas.push(`Title (${keyword.area_counts.title})`);
            if (keyword.area_counts.content > 0) areas.push(`Content (${keyword.area_counts.content})`);
            if (keyword.area_counts.meta_description > 0) areas.push(`Meta (${keyword.area_counts.meta_description})`);
            if (keyword.area_counts.headings > 0) areas.push(`Headings (${keyword.area_counts.headings})`);
            const context = keyword.context.length > 0 ? keyword.context[0].substring(0, 100) + '...' : 'No context';
            const relevanceClass = keyword.relevance_score >= 70 ? 'relevance-high' : keyword.relevance_score >= 40 ? 'relevance-medium' : 'relevance-low';
            const row = $('<tr></tr>');
            const keywordText = keyword.is_smart_match ? `✨ ${keyword.keyword}` : keyword.keyword;
            row.append($('<td class="keyword-cell"></td>').text(keywordText));
            row.append($('<td class="count-cell"></td>').text(keyword.count));
            row.append($('<td class="density-cell"></td>').text(keyword.density + '%'));
            row.append($('<td></td>').append($('<span class="status-badge"></span>').addClass('status-' + keyword.status).text(keyword.status)));
            row.append($('<td class="relevance-cell"></td>').append($('<span class="relevance-score"></span>').addClass(relevanceClass).text(keyword.relevance_score || 0)));
            row.append($('<td class="areas-found"></td>').text(areas.join(', ') || 'Not found'));
            row.append($('<td class="context-preview"></td>').text(context));
            tbody.append(row);
        });
    }

    function displayRecommendations(recommendations) {
        const container = $('#recommendations-content');
        container.empty();
        if (!recommendations || recommendations.length === 0) {
            container.html('<p>No specific recommendations.</p>');
            return;
        }
        const recContainer = $('<div class="recommendations-container"></div>');
        recommendations.forEach(rec => {
            const item = $('<div class="recommendation-item"></div>');
            if (typeof rec === 'string') item.addClass('recommendation-simple').append($('<p></p>').text(rec));
            else {
                item.addClass('recommendation-' + (rec.type || 'info')).addClass('priority-' + (rec.priority || 'medium'));
                const header = $('<div class="recommendation-header"></div>');
                header.append($('<h5 class="recommendation-title"></h5>').text(rec.title || 'Recommendation'));
                const content = $('<div class="recommendation-content"></div>');
                content.append($('<p class="recommendation-message"></p>').text(rec.message || rec));
                item.append(header).append(content);
            }
            recContainer.append(item);
        });
        container.append(recContainer);
    }

    // Export Trigger
    $('#export-csv-btn').on('click', () => exportAnalysis('csv'));
    $('#export-json-btn').on('click', () => exportAnalysis('json'));

    function exportAnalysis(format) {
        if (!analysisData) return;
        const form = $('<form>', { method: 'POST', action: artitechcore_keyword_data.ajaxurl, target: '_blank' });
        form.append($('<input>', { type: 'hidden', name: 'action', value: 'artitechcore_export_keyword_analysis' }));
        form.append($('<input>', { type: 'hidden', name: 'nonce', value: artitechcore_keyword_data.nonce }));
        form.append($('<input>', { type: 'hidden', name: 'format', value: format }));
        form.append($('<input>', { type: 'hidden', name: 'analysis_data', value: JSON.stringify(analysisData) }));
        $('body').append(form);
        form.submit();
        form.remove();
    }
});
