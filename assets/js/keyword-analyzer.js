jQuery(document).ready(function ($) {
    let analysisData = null;

    // Load pages on page load
    loadPages();

    // Keyword count display
    $('#aiopms_keywords_input').on('input', function () {
        const text = $(this).val();
        const keywords = text.split(/[\r\n,]+/).filter(k => k.trim().length > 0);
        $('#keyword-count').text('Keywords: ' + keywords.length);
    });

    // Form submission
    $('#aiopms-keyword-analysis-form').on('submit', function (e) {
        e.preventDefault();

        const pageId = $('#aiopms_page_select').val();
        const keywords = $('#aiopms_keywords_input').val();

        if (!pageId || !keywords.trim()) {
            alert('Please select a page and enter keywords to analyze.');
            return;
        }

        analyzeKeywords(pageId, keywords);
    });

    // Export functions
    $('#export-csv-btn').on('click', function () {
        if (analysisData) {
            exportAnalysis('csv');
        }
    });

    $('#export-json-btn').on('click', function () {
        if (analysisData) {
            exportAnalysis('json');
        }
    });

    function loadPages() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aiopms_get_pages',
                nonce: aiopms_keyword_data.nonce
            },
            success: function (response) {
                if (response.success) {
                    const select = $('#aiopms_page_select');
                    select.empty();
                    select.append('<option value="">Select a page...</option>');

                    response.data.forEach(function (page) {
                        // Use .text() for safer rendering of page titles
                        const option = $('<option></option>')
                            .attr('value', page.id)
                            .text(`${page.title} (${page.type})`);
                        select.append(option);
                    });
                } else {
                    console.error('Failed to load pages:', response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error loading pages:', error);
            }
        });
    }

    function analyzeKeywords(pageId, keywords) {
        const btn = $('#aiopms-analyze-btn');
        const btnText = btn.find('.btn-text');
        const spinner = btn.find('.dg10-spinner');

        // Show loading state
        btn.prop('disabled', true);
        btnText.addClass('dg10-hidden');
        spinner.removeClass('dg10-hidden');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aiopms_analyze_keywords',
                page_id: pageId,
                keywords: keywords,
                nonce: aiopms_keyword_data.nonce
            },
            success: function (response) {
                if (response.success) {
                    analysisData = response.data;
                    displayResults(response.data);
                    $('#aiopms-analysis-results').removeClass('dg10-hidden');
                } else {
                    alert('Analysis failed: ' + (response.data || 'Unknown error'));
                }
            },
            error: function (xhr, status, error) {
                console.error('Analysis error:', error);
                alert('Analysis failed. Please try again.');
            },
            complete: function () {
                // Hide loading state
                btn.prop('disabled', false);
                btnText.removeClass('dg10-hidden');
                spinner.addClass('dg10-hidden');
            }
        });
    }

    function showNotification(message, type = 'info') {
        const notification = $(`
            <div class="aiopms-notification notification-${type}">
                <span class="notification-message"></span>
                <button class="notification-close" aria-label="Close notification">&times;</button>
            </div>
        `);

        notification.find('.notification-message').text(message);
        $('body').append(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.fadeOut(300, function () {
                $(this).remove();
            });
        }, 5000);

        // Manual close
        notification.find('.notification-close').on('click', function () {
            notification.fadeOut(300, function () {
                $(this).remove();
            });
        });
    }

    function displayResults(data) {
        // Display page info
        displayPageInfo(data.page_info);

        // Display summary
        displaySummary(data.summary);

        // Display keywords table
        displayKeywordsTable(data.keywords);

        // Display recommendations
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
            if (typeof item.value === 'string' || typeof item.value === 'number') {
                val.text(item.value);
            } else {
                val.append(item.value);
            }
            el.append(val);
            grid.append(el);
        });

        container.append(grid);
    }

    function displaySummary(summary) {
        const container = $('#summary-content');
        container.empty();

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

            el.append(num);
            el.append($('<div class="summary-label"></div>').text(item.label));
            summaryGrid.append(el);
        });

        container.append(summaryGrid);

        if (summary.performance_metrics) {
            const perf = $('<div class="performance-metrics"><h5>Performance Metrics</h5></div>');
            const metricsGrid = $('<div class="metrics-grid"></div>');

            const metrics = [
                { label: 'Well Optimized', value: summary.performance_metrics.well_optimized, class: 'good' },
                { label: 'Over Optimized', value: summary.performance_metrics.over_optimized, class: 'warning' },
                { label: 'Under Optimized', value: summary.performance_metrics.under_optimized, class: 'info' },
                { label: 'Not Found', value: summary.performance_metrics.not_found, class: 'error' }
            ];

            metrics.forEach(m => {
                const mel = $('<div class="metric-item"></div>');
                mel.append($('<span class="metric-label"></span>').text(m.label + ':'));
                mel.append($('<span class="metric-value"></span>').addClass(m.class).text(m.value));
                metricsGrid.append(mel);
            });

            perf.append(metricsGrid);
            container.append(perf);
        }
    }

    function displayKeywordsTable(keywords) {
        const tbody = $('#keywords-table tbody');
        tbody.empty();

        keywords.forEach(function (keyword) {
            const areas = [];
            if (keyword.area_counts.title > 0) areas.push(`Title (${keyword.area_counts.title})`);
            if (keyword.area_counts.content > 0) areas.push(`Content (${keyword.area_counts.content})`);
            if (keyword.area_counts.meta_description > 0) areas.push(`Meta (${keyword.area_counts.meta_description})`);
            if (keyword.area_counts.excerpt > 0) areas.push(`Excerpt (${keyword.area_counts.excerpt})`);
            if (keyword.area_counts.headings > 0) areas.push(`Headings (${keyword.area_counts.headings})`);

            const context = keyword.context.length > 0 ?
                keyword.context[0].substring(0, 100) + '...' : 'No context found';

            const relevanceScore = keyword.relevance_score || 0;
            const relevanceClass = relevanceScore >= 70 ? 'relevance-high' :
                relevanceScore >= 40 ? 'relevance-medium' : 'relevance-low';

            const row = $('<tr></tr>');
            row.append($('<td class="keyword-cell"></td>').text(keyword.keyword));
            row.append($('<td class="count-cell"></td>').text(keyword.count));
            row.append($('<td class="density-cell"></td>').text(keyword.density + '%'));
            row.append($('<td></td>').append($('<span class="status-badge"></span>').addClass('status-' + keyword.status).text(keyword.status)));
            row.append($('<td class="relevance-cell"></td>').append($('<span class="relevance-score"></span>').addClass(relevanceClass).text(relevanceScore)));
            row.append($('<td class="areas-found"></td>').text(areas.join(', ') || 'Not found'));
            row.append($('<td class="context-preview"></td>').text(context));

            tbody.append(row);
        });
    }

    function displayRecommendations(recommendations) {
        const container = $('#recommendations-content');
        container.empty();

        if (!recommendations || recommendations.length === 0) {
            container.html('<p>No specific recommendations available.</p>');
            return;
        }

        const recContainer = $('<div class="recommendations-container"></div>');

        recommendations.forEach(rec => {
            const item = $('<div class="recommendation-item"></div>');

            if (typeof rec === 'string') {
                item.addClass('recommendation-simple').append($('<p></p>').text(rec));
            } else {
                const typeClass = rec.type || 'info';
                const priorityClass = rec.priority || 'medium';
                item.addClass('recommendation-' + typeClass).addClass('priority-' + priorityClass);

                const header = $('<div class="recommendation-header"></div>');
                header.append($('<h5 class="recommendation-title"></h5>').text(rec.title || 'Recommendation'));
                header.append($('<span class="recommendation-type"></span>').text(rec.type || 'info'));

                const content = $('<div class="recommendation-content"></div>');
                content.append($('<p class="recommendation-message"></p>').text(rec.message || rec));

                if (rec.keywords && rec.keywords.length > 0) {
                    content.append($('<div class="recommendation-keywords"></div>').html('<strong>Keywords:</strong> ' + rec.keywords.join(', ')));
                }

                if (rec.action) {
                    content.append($('<div class="recommendation-action"></div>').html('<strong>Action:</strong> ' + rec.action));
                }

                item.append(header).append(content);
            }
            recContainer.append(item);
        });

        container.append(recContainer);
    }

    function exportAnalysis(format) {
        if (!analysisData) return;

        const form = $('<form>', {
            method: 'POST',
            action: ajaxurl,
            target: '_blank'
        });

        form.append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'aiopms_export_keyword_analysis'
        }));

        form.append($('<input>', {
            type: 'hidden',
            name: 'nonce',
            value: aiopms_keyword_data.nonce
        }));

        form.append($('<input>', {
            type: 'hidden',
            name: 'format',
            value: format
        }));

        form.append($('<input>', {
            type: 'hidden',
            name: 'analysis_data',
            value: JSON.stringify(analysisData)
        }));

        $('body').append(form);
        form.submit();
        form.remove();
    }
});
