jQuery(document).ready(function ($) {
    let hierarchyData = null;
    let currentView = 'grid'; // Default view is now grid

    // 1. Initialize the hierarchy visualization
    function initHierarchy() {
        // Check if aiopmsHierarchy object is available
        if (typeof aiopmsHierarchy === 'undefined') {
            console.error('AIOPMS: aiopmsHierarchy object not found!');
            return;
        }

        // Fetch hierarchy data from REST API
        $.ajax({
            url: aiopmsHierarchy.rest_url + 'hierarchy',
            method: 'GET',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', aiopmsHierarchy.nonce);
            },
            success: function (data) {
                hierarchyData = data;
                // Render the default view
                switchView(currentView);
                setupEventHandlers();
            },
            error: function (xhr, status, error) {
                console.error('AIOPMS: Hierarchy fetch error:', error);
                const errorHtml = `
                    <div class="dg10-notice dg10-notice-error">
                        <p><strong>Error:</strong> Failed to load hierarchy data. ${error}</p>
                    </div>
                `;
                $('#abpcwa-hierarchy-view-container').prepend(errorHtml);
                $('.aiopms-loading-state').hide();
            }
        });
    }

    // 2. Switch between different visualization views
    function switchView(view) {
        if (!hierarchyData) return;

        currentView = view;

        // Update button styles in DG10 group
        $('.abpcwa-view-controls .dg10-btn').removeClass('dg10-btn-primary').addClass('dg10-btn-outline');
        $('.abpcwa-view-controls .dg10-btn[data-view="' + view + '"]').removeClass('dg10-btn-outline').addClass('dg10-btn-primary');

        // Show the correct view container
        $('.abpcwa-hierarchy-view').removeClass('active-view').hide();
        $('#abpcwa-hierarchy-' + view).addClass('active-view').fadeIn(300);

        // Call the appropriate render function
        switch (view) {
            case 'tree':
                renderTreeView();
                break;
            case 'orgchart':
                renderOrgChartView();
                break;
            case 'grid':
                renderGridView();
                break;
        }
    }

    // 3. Render Functions for each view
    function renderTreeView() {
        if ($.jstree.reference('#abpcwa-hierarchy-tree')) {
            $('#abpcwa-hierarchy-tree').jstree(true).settings.core.data = hierarchyData;
            $('#abpcwa-hierarchy-tree').jstree(true).refresh();
        } else {
            $('#abpcwa-hierarchy-tree').jstree({
                'core': {
                    'data': hierarchyData,
                    'themes': { 'name': 'default', 'responsive': true },
                    'check_callback': false
                },
                'plugins': ['search', 'types'],
                'types': {
                    'default': { 'icon': 'dashicons dashicons-admin-page' },
                    'page': { 'icon': 'dashicons dashicons-admin-page' },
                    'cpt_archive': { 'icon': 'dashicons dashicons-category' },
                    'cpt_post': { 'icon': 'dashicons dashicons-admin-post' }
                }
            });
        }
    }

    function renderOrgChartView() {
        const container = $('#abpcwa-hierarchy-orgchart');
        container.empty();

        const width = container.width() || 1000;
        const height = 600;

        const svg = d3.select(container.get(0)).append("svg")
            .attr("width", "100%")
            .attr("height", height)
            .attr("viewBox", `0 0 ${width} ${height}`);

        const g = svg.append("g");

        const zoom = d3.zoom()
            .scaleExtent([0.1, 3])
            .on("zoom", (event) => g.attr("transform", event.transform));

        svg.call(zoom);

        // Stratify data
        // D3 stratify requires a single root. WordPress has multiple top-level pages (forest).
        // We must add a virtual root node to connect them all.
        let stratifyData = JSON.parse(JSON.stringify(hierarchyData)); // Deep clone

        // 1. Add Virtual Root
        stratifyData.push({
            id: 'virtual_root',
            parent: '#', /* Will be null in accessor */
            text: 'Website Root',
            data: { text: 'Website Root' } // Ensure data property exists for text rendering
        });

        // 2. Reparent top-level items to Virtual Root
        stratifyData.forEach(d => {
            if (d.id !== 'virtual_root' && d.parent === '#') {
                d.parent = 'virtual_root';
            }
        });

        // 3. Stratify
        let root;
        try {
            root = d3.stratify()
                .id(d => d.id)
                .parentId(d => d.parent === '#' ? null : d.parent)
                (stratifyData);
        } catch (error) {
            console.error('D3 Stratify Error:', error);
            container.html('<div class="dg10-notice dg10-notice-error"><p>Failed to generate Org Chart: ' + error.message + '</p></div>');
            return;
        }

        const treeLayout = d3.tree().size([width - 100, height - 100]);
        treeLayout(root);

        // Links
        g.selectAll(".link")
            .data(root.links())
            .enter().append("path")
            .attr("class", "dg10-org-link")
            .attr("d", d3.linkVertical()
                .x(d => d.x)
                .y(d => d.y))
            .attr("fill", "none")
            .attr("stroke", "rgba(139, 92, 246, 0.2)")
            .attr("stroke-width", 2);

        // Nodes
        const node = g.selectAll(".node")
            .data(root.descendants())
            .enter().append("g")
            .attr("class", "dg10-org-node")
            .attr("transform", d => `translate(${d.x},${d.y})`);

        node.append("circle")
            .attr("r", 6)
            .attr("fill", "#8b5cf6");

        node.append("text")
            .attr("dy", ".31em")
            .attr("y", d => d.children ? -20 : 20)
            .attr("text-anchor", "middle")
            .text(d => d.data.text)
            .style("font-size", "12px")
            .style("fill", "var(--dg10-text-main)")
            .style("font-family", "inherit");

        // Initial zoom to fit
        svg.call(zoom.transform, d3.zoomIdentity.translate(50, 50).scale(0.8));
    }

    function renderGridView() {
        const container = $('#abpcwa-hierarchy-grid');
        container.empty();

        const roots = hierarchyData.filter(d => d.parent === '#');
        const childrenMap = {};

        hierarchyData.forEach(d => {
            if (d.parent !== '#') {
                if (!childrenMap[d.parent]) childrenMap[d.parent] = [];
                childrenMap[d.parent].push(d);
            }
        });

        const gridWrapper = $('<div class="dg10-hierarchy-grid-wrapper"></div>');

        function buildGridItem(item, level = 0) {
            const hasChildren = childrenMap[item.id] && childrenMap[item.id].length > 0;
            const itemEl = $(`
                <div class="dg10-grid-item-container" data-level="${level}">
                    <div class="dg10-grid-card ${item.meta && item.meta.is_homepage ? 'is-homepage' : ''}">
                        <div class="dg10-grid-card-content">
                            <div class="dg10-grid-card-header">
                                <span class="dg10-grid-icon">${item.meta && item.meta.is_homepage ? '🏠' : '📄'}</span>
                                <span class="dg10-grid-title">${item.text}</span>
                                <span class="dg10-grid-badge status-${item.meta ? item.meta.status : 'unknown'}">${item.meta ? item.meta.status : 'Page'}</span>
                            </div>
                            ${item.meta && item.meta.description ? `<p class="dg10-grid-desc">${item.meta.description}</p>` : ''}
                            <div class="dg10-grid-meta">
                                <span>👤 ${item.meta ? item.meta.author.split(' (')[0] : 'Admin'}</span>
                                <span>📅 ${item.meta ? item.meta.published : ''}</span>
                            </div>
                            <div class="dg10-grid-actions">
                                <a href="${item.a_attr.href}" target="_blank" class="dg10-btn dg10-btn-xs dg10-btn-outline">View</a>
                                ${hasChildren ? `<button class="dg10-btn dg10-btn-xs dg10-btn-primary toggle-children">Sub-pages (${childrenMap[item.id].length})</button>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `);

            if (hasChildren) {
                const childrenContainer = $('<div class="dg10-grid-children" style="display:none;"></div>');
                childrenMap[item.id].forEach(child => {
                    childrenContainer.append(buildGridItem(child, level + 1));
                });
                itemEl.append(childrenContainer);
            }

            return itemEl;
        }

        roots.forEach(root => {
            gridWrapper.append(buildGridItem(root));
        });

        container.append(gridWrapper);

        // Grid Event Handlers
        container.off('click', '.toggle-children').on('click', '.toggle-children', function (e) {
            e.preventDefault();
            const btn = $(this);
            const children = btn.closest('.dg10-grid-item-container').find('> .dg10-grid-children');
            children.slideToggle(300);
            btn.toggleClass('is-active');
        });
    }

    // 4. Event Handlers
    function setupEventHandlers() {
        // View switcher
        $('.abpcwa-view-controls').on('click', '.dg10-btn', function () {
            const view = $(this).data('view');
            if (view !== currentView) switchView(view);
        });

        // Search
        let searchTimer;
        $('#abpcwa-hierarchy-search').on('input', function () {
            clearTimeout(searchTimer);
            const val = $(this).val();
            searchTimer = setTimeout(() => {
                if (currentView === 'tree') {
                    $('#abpcwa-hierarchy-tree').jstree(true).search(val);
                } else if (currentView === 'grid') {
                    filterGrid(val);
                }
            }, 300);
        });

        // Copy Hierarchy
        $('#aiopms-copy-hierarchy').on('click', function () {
            const type = $('#aiopms-copy-type').val();
            copyHierarchy(type);
        });

        // Export Actions
        $('.aiopms-export-trigger').on('click', function () {
            const type = $(this).data('type');
            const nonce = $('#aiopms-export-nonce').val();
            const url = ajaxurl + '?action=aiopms_export_' + type + '&nonce=' + nonce;
            window.location.href = url;
        });
    }

    function filterGrid(val) {
        val = val.toLowerCase();
        $('.dg10-grid-card').each(function () {
            const text = $(this).find('.dg10-grid-title').text().toLowerCase();
            const desc = $(this).find('.dg10-grid-desc').text().toLowerCase();
            if (text.includes(val) || desc.includes(val)) {
                $(this).closest('.dg10-grid-item-container').show();
                $(this).closest('.dg10-grid-children').show(); // Show parents if child matches
            } else {
                $(this).closest('.dg10-grid-item-container').hide();
            }
        });
        if (!val) $('.dg10-grid-children').hide();
    }

    function copyHierarchy(type) {
        if (!hierarchyData) return;

        let text = '';
        const buildLine = (item, level) => {
            const indent = '  '.repeat(level);
            if (type === 'titles') text += `${indent}${item.text}\n`;
            else if (type === 'urls') text += `${indent}${item.a_attr.href}\n`;
            else text += `${indent}${item.text} - ${item.a_attr.href}\n`;
        };

        const traverse = (parentId, level) => {
            hierarchyData.filter(d => d.parent === parentId).forEach(item => {
                buildLine(item, level);
                traverse(item.id, level + 1);
            });
        };

        traverse('#', 0);

        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        document.execCommand('copy');
        $temp.remove();

        const btn = $('#aiopms-copy-hierarchy');
        const originalText = btn.html();
        btn.html('✅ Copied!').addClass('dg10-btn-success');
        setTimeout(() => {
            btn.html(originalText).removeClass('dg10-btn-success');
        }, 2000);
    }

    initHierarchy();
});
