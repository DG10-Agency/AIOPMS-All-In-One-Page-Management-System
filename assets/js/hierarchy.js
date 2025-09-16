jQuery(document).ready(function($) {
    console.log('AIOPMS: Hierarchy script loaded');
    console.log('AIOPMS: jQuery version:', $.fn.jquery);
    console.log('AIOPMS: jsTree available:', typeof $.fn.jstree !== 'undefined');
    console.log('AIOPMS: D3 available:', typeof d3 !== 'undefined');
    
    let hierarchyData = null; // To store fetched data
    let currentView = 'grid'; // Default view

    // 1. Initialize the hierarchy visualization
    function initHierarchy() {
        console.log('AIOPMS: Initializing hierarchy');
        
        // Check if DOM elements are available
        const treeContainer = $('#abpcwa-hierarchy-tree');
        console.log('AIOPMS: Tree container found:', treeContainer.length);
        
        if (treeContainer.length === 0) {
            console.error('AIOPMS: Tree container not found!');
            return;
        }
        
        // Check if aiopmsHierarchy object is available
        if (typeof aiopmsHierarchy === 'undefined') {
            console.error('AIOPMS: aiopmsHierarchy object not found!');
            treeContainer.html('<div class="abpcwa-error">Error: Hierarchy configuration not loaded. Please refresh the page.</div>');
            return;
        }
        
        // Set initial loading message for the default view with better UX
        const loadingHtml = `
            <div class="abpcwa-loading" style="text-align: center; padding: 60px 20px; color: #6b7280;">
                <div style="width: 40px; height: 40px; border: 4px solid #e5e7eb; border-top: 4px solid #8b5cf6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                <h3 style="margin: 0 0 10px 0; color: #374151; font-size: 18px;">Loading Page Hierarchy</h3>
                <p style="margin: 0; font-size: 14px; opacity: 0.8;">Fetching and organizing your pages...</p>
            </div>
        `;
        
        // Only show loading in the default view (grid)
        $('#abpcwa-hierarchy-grid').html(loadingHtml);
        
        // Ensure only the default view is visible initially
        $('.abpcwa-hierarchy-view').removeClass('active-view');
        $('#abpcwa-hierarchy-grid').addClass('active-view');

        // Fetch hierarchy data from REST API
        console.log('AIOPMS: Fetching hierarchy data from:', aiopmsHierarchy.rest_url + 'hierarchy');
        console.log('AIOPMS: Nonce:', aiopmsHierarchy.nonce);
        
        $.ajax({
            url: aiopmsHierarchy.rest_url + 'hierarchy',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', aiopmsHierarchy.nonce);
            },
            success: function(data) {
                console.log('AIOPMS: Hierarchy data received:', data);
                hierarchyData = data;
                // Render the default view
                switchView(currentView);
            },
            error: function(xhr, status, error) {
                console.error('AIOPMS: Hierarchy fetch error:', xhr, status, error);
                
                let errorMessage = 'Error loading hierarchy: ' + error;
                let errorDetails = '';
                
                if (xhr.status === 403) {
                    errorMessage = 'Access Denied';
                    errorDetails = 'You do not have permission to view the page hierarchy. Please contact your administrator.';
                } else if (xhr.status === 404) {
                    errorMessage = 'Service Not Found';
                    errorDetails = 'The hierarchy service is not available. Please refresh the page and try again.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server Error';
                    errorDetails = 'There was a server error while loading the hierarchy. Please try again later.';
                } else if (xhr.status === 0) {
                    errorMessage = 'Connection Error';
                    errorDetails = 'Unable to connect to the server. Please check your internet connection.';
                } else {
                    errorDetails = 'Status: ' + xhr.status + '<br>Response: ' + (xhr.responseText || 'No response');
                }
                
                // Display user-friendly error message
                const errorHtml = `
                    <div class="abpcwa-error" style="text-align: center; padding: 40px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b;">
                        <h3 style="margin: 0 0 15px 0; color: #dc2626;">⚠️ ${errorMessage}</h3>
                        <p style="margin: 0 0 20px 0; line-height: 1.5;">${errorDetails}</p>
                        <button onclick="location.reload()" class="button button-primary" style="background: #dc2626; border-color: #dc2626; color: white;">
                            🔄 Retry
                        </button>
                    </div>
                `;
                
                $('.abpcwa-hierarchy-view.active-view').html(errorHtml);
                console.error('Hierarchy Error:', xhr);
            }
        });
    }

    // 2. Switch between different visualization views
    function switchView(view) {
        console.log('AIOPMS: Switching to view:', view);
        console.log('AIOPMS: Hierarchy data available:', !!hierarchyData);
        
        if (!hierarchyData) {
            console.warn('AIOPMS: Cannot switch view - no hierarchy data loaded');
            return; // Don't switch if data isn't loaded
        }

        currentView = view;

        // Update button styles
        $('.abpcwa-view-controls .button').removeClass('button-primary');
        $('.abpcwa-view-controls .button[data-view="' + view + '"]').addClass('button-primary');

        // Hide all views first, then show only the selected one
        $('.abpcwa-hierarchy-view').removeClass('active-view').hide();
        $('#abpcwa-hierarchy-' + view).addClass('active-view').show();

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
        console.log('AIOPMS: Rendering tree view');
        console.log('AIOPMS: Hierarchy data for tree view:', hierarchyData);
        
        // Remove any existing tooltip handlers to prevent duplicates
        $('#abpcwa-hierarchy-tree').off('hover_node.jstree');

        // Check if tree is already initialized
        if ($.jstree.reference('#abpcwa-hierarchy-tree')) {
            console.log('AIOPMS: Refreshing existing jstree');
            $('#abpcwa-hierarchy-tree').jstree(true).settings.core.data = hierarchyData;
            $('#abpcwa-hierarchy-tree').jstree(true).refresh();
        } else {
            console.log('AIOPMS: Initializing new jstree with data:', hierarchyData);
            $('#abpcwa-hierarchy-tree').jstree({
                'core': {
                    'data': hierarchyData,
                    'themes': { 'name': 'default', 'responsive': true },
                    'check_callback': false // Read-only
                },
                'plugins': ['search']
            }).on('ready.jstree', function() {
                console.log('AIOPMS: jstree initialized successfully');
            }).on('error.jstree', function(e, data) {
                console.error('AIOPMS: jstree error:', data);
            });
        }

        // Add hover tooltips with meta information (works for both new and refreshed trees)
        $('#abpcwa-hierarchy-tree').on('hover_node.jstree', function(e, data) {
            const node = data.node;
            if (node.original && node.original.meta) {
                const meta = node.original.meta;
                let tooltipContent = `<strong>${node.text}</strong>`;

                // Add status information
                let statusText = 'Page';
                if (meta.status) {
                    switch(meta.status) {
                        case 'publish': statusText = 'Published'; break;
                        case 'draft': statusText = 'Draft'; break;
                        case 'pending': statusText = 'Pending'; break;
                        case 'private': statusText = 'Private'; break;
                        case 'trash': statusText = 'Trash'; break;
                    }
                }
                tooltipContent += `<br>📋 Status: ${statusText}`;

                if (meta.description) {
                    tooltipContent += `<br>📝 ${meta.description}`;
                }

                if (meta.published) {
                    tooltipContent += `<br>📅 Published: ${meta.published}`;
                }

                if (meta.modified && meta.modified !== meta.published) {
                    tooltipContent += `<br>✏️ Modified: ${meta.modified}`;
                }

                if (meta.author) {
                    tooltipContent += `<br>👤 ${meta.author}`;
                }

                // Create and show tooltip
                const $node = $(data.instance.get_node(data.node, true));
                $node.attr('title', ''); // Clear default title

                // Use HTML tooltips for better formatting
                $node.attr('data-original-title', tooltipContent);

                // Enable bootstrap tooltips if available, otherwise use native title
                if (typeof $.fn.tooltip === 'function') {
                    $node.tooltip({
                        html: true,
                        title: tooltipContent,
                        placement: 'auto right'
                    }).tooltip('show');
                } else {
                    // Fallback to basic title attribute
                    $node.attr('title', tooltipContent.replace(/<br>/g, '\n').replace(/<[^>]*>/g, ''));
                }
            }
        });
    }

    function renderOrgChartView() {
        console.log('AIOPMS: Rendering org chart view');
        const container = $('#abpcwa-hierarchy-orgchart');
        container.empty().html('<div class="abpcwa-loading">Rendering Org Chart...</div>');

        // Use setTimeout to ensure DOM is ready
        setTimeout(() => {
            try {
                const width = container.width() || 1000;
                const height = container.height() || 600;
                
                // Clear any previous content
                container.empty();
                
                // Create SVG container with proper dimensions
                const svg = d3.select(container.get(0)).append("svg")
                    .attr("width", width)
                    .attr("height", height)
                    .attr("viewBox", `0 0 ${width} ${height}`)
                    .attr("preserveAspectRatio", "xMidYMid meet")
                    .style("background", "#fff");

                // Create main group for zooming
                const g = svg.append("g");

                // Add zoom behavior
                const zoom = d3.zoom()
                    .scaleExtent([0.1, 4])
                    .on("zoom", (event) => {
                        g.attr("transform", event.transform);
                    });

                svg.call(zoom);

                // Data transformation for D3 - ensure proper hierarchy structure
                let validData = hierarchyData.filter(d => d.id && d.text);
                
                // Ensure we have exactly one root node for D3.stratify()
                // If multiple roots exist, create a virtual root node
                const roots = validData.filter(d => d.parent === '#');
                
                if (roots.length === 0) {
                    throw new Error('No root nodes found in hierarchy data');
                }
                
                let processedData = [...validData];
                
                // If multiple roots, create a virtual root to connect them
                if (roots.length > 1) {
                    const virtualRootId = 'virtual-root-' + Date.now();
                    processedData = processedData.map(d => {
                        if (d.parent === '#') {
                            return {...d, parent: virtualRootId};
                        }
                        return d;
                    });
                    
                    // Add virtual root node
                    processedData.push({
                        id: virtualRootId,
                        parent: null,
                        text: 'Virtual Root',
                        type: 'virtual',
                        state: {opened: true, disabled: false},
                        li_attr: {'data-page-id': 'virtual', 'data-page-status': 'virtual'},
                        a_attr: {href: '#', target: '_self', title: 'Virtual Root Node'}
                    });
                }
                
                // Ensure all data has proper string values for D3.stratify()
                processedData = processedData.map(d => ({
                    ...d,
                    id: d.id ? d.id.toString() : '',
                    parent: d.parent ? (d.parent === '#' ? null : d.parent.toString()) : null
                })).filter(d => d.id); // Filter out any items without IDs
                
                const root = d3.stratify()
                    .id(d => d.id)
                    .parentId(d => d.parent)
                    (processedData);

                // Create tree layout with proper spacing
                const treeLayout = d3.tree()
                    .nodeSize([120, 200])
                    .separation((a, b) => (a.parent == b.parent ? 1 : 2) / a.depth);

                const treeData = treeLayout(root);

                // Calculate bounds to center the tree
                let x0 = Infinity;
                let x1 = -Infinity;
                let y0 = Infinity;
                let y1 = -Infinity;
                
                treeData.descendants().forEach(d => {
                    if (d.x < x0) x0 = d.x;
                    if (d.x > x1) x1 = d.x;
                    if (d.y < y0) y0 = d.y;
                    if (d.y > y1) y1 = d.y;
                });

                // Center the tree
                const treeWidth = x1 - x0;
                const treeHeight = y1 - y0;
                const initialX = (width - treeWidth) / 2 - x0;
                const initialY = 60; // Top margin

                g.attr("transform", `translate(${initialX}, ${initialY})`);

                // Create curved links
                const link = g.selectAll(".link")
                    .data(treeData.links())
                    .enter().append("path")
                    .attr("class", "link")
                    .attr("d", d3.linkHorizontal()
                        .x(d => d.y)
                        .y(d => d.x))
                    .attr("fill", "none")
                    .attr("stroke", "#999")
                    .attr("stroke-width", 1.5)
                    .attr("stroke-opacity", 0.6);

                // Create nodes
                const node = g.selectAll(".node")
                    .data(treeData.descendants())
                    .enter().append("g")
                    .attr("class", "node")
                    .attr("transform", d => `translate(${d.y},${d.x})`);

                // Add rectangles for nodes
                node.append("rect")
                    .attr("width", 140)
                    .attr("height", 50)
                    .attr("x", -70)
                    .attr("y", -25)
                    .attr("rx", 6)
                    .attr("ry", 6)
                    .attr("fill", d => d.depth === 0 ? "#2271b1" : "#72aee6")
                    .attr("stroke", "#fff")
                    .attr("stroke-width", 2)
                    .attr("cursor", "pointer")
                    .on("click", (event, d) => {
                        if (d.data.a_attr && d.data.a_attr.href) {
                            window.open(d.data.a_attr.href, d.data.a_attr.target || '_blank');
                        }
                    });


                // Add text labels
                node.append("text")
                    .attr("dy", "0.31em")
                    .attr("text-anchor", "middle")
                    .attr("fill", "#fff")
                    .attr("font-size", "12px")
                    .attr("font-family", "sans-serif")
                    .attr("font-weight", "500")
                    .text(d => d.data.text)
                    .call(wrapText, 130); // Wrap text to fit in rectangle

                // Text wrapping function
                function wrapText(text, width) {
                    text.each(function() {
                        const text = d3.select(this);
                        const words = text.text().split(/\s+/).reverse();
                        let word;
                        let line = [];
                        let lineNumber = 0;
                        const lineHeight = 1.1;
                        const y = text.attr("y");
                        const dy = parseFloat(text.attr("dy"));
                        let tspan = text.text(null).append("tspan")
                            .attr("x", 0)
                            .attr("y", y)
                            .attr("dy", dy + "em");
                        
                        while (word = words.pop()) {
                            line.push(word);
                            tspan.text(line.join(" "));
                            if (tspan.node().getComputedTextLength() > width) {
                                line.pop();
                                tspan.text(line.join(" "));
                                line = [word];
                                tspan = text.append("tspan")
                                    .attr("x", 0)
                                    .attr("y", y)
                                    .attr("dy", ++lineNumber * lineHeight + dy + "em")
                                    .text(word);
                            }
                        }
                    });
                }

                // Reset zoom to fit content
                setTimeout(() => {
                    const bounds = g.node().getBBox();
                    const fullWidth = bounds.width;
                    const fullHeight = bounds.height;
                    const scale = 0.9 / Math.max(fullWidth / width, fullHeight / height);
                    const translate = [
                        width / 2 - scale * (bounds.x + bounds.width / 2),
                        height / 2 - scale * (bounds.y + bounds.height / 2)
                    ];
                    
                    svg.call(zoom.transform, d3.zoomIdentity
                        .translate(translate[0], translate[1])
                        .scale(scale)
                    );
                    
                    // Add zoom controls after rendering
                    addZoomControls(container);
                }, 100);

            } catch (error) {
                console.error('Org Chart Error:', error);
                container.html('<div class="abpcwa-error">Error rendering Org Chart: ' + error.message + '</div>');
            }
        }, 100);
    }

    function renderGridView() {
        console.log('AIOPMS: Rendering grid view');
        const container = $('#abpcwa-hierarchy-grid');
        console.log('Grid View: Container found:', container.length);
        container.empty().html('<div class="abpcwa-loading">Rendering Grid View...</div>');

        // Use setTimeout to ensure DOM is ready
        setTimeout(() => {
            try {
                container.empty();

                // Filter valid pages
                const validPages = hierarchyData.filter(d => d.id && d.text);
                console.log('Grid View: Valid pages count:', validPages.length);

                if (validPages.length === 0) {
                    container.html('<div class="abpcwa-error">No pages found to display</div>');
                    return;
                }

                console.log('Grid View: Rendering ' + validPages.length + ' pages');
                console.log('Parent values:', validPages.map(p => p.parent));

                // Group pages by parent for hierarchy
                const pagesByParent = {};
                validPages.forEach(page => {
                    const parentId = page.parent === '#' ? 'root' : page.parent;
                    if (!pagesByParent[parentId]) {
                        pagesByParent[parentId] = [];
                    }
                    pagesByParent[parentId].push(page);
                });

                console.log('Pages by parent:', pagesByParent);

                // Handle case where no root pages are found
                const rootPages = pagesByParent['root'] || [];
                if (rootPages.length === 0) {
                    container.html('<div class="abpcwa-error">No root pages found. The grid view requires at least one page with no parent (parent set to "#").</div>');
                    return;
                }

                // Create simple grid cards with hierarchy
                function createGridCard(page, level = 0) {
                    const card = $('<div class="aiopms-grid-card level-' + level + '"></div>')
                        .attr('data-page-id', page.id)
                        .attr('data-level', level);
                    
                    // Add homepage data attribute if this is the homepage
                    if (page.meta && page.meta.is_homepage) {
                        card.attr('data-is-homepage', 'true');
                    }

                    // Add level badge for non-root levels
                    if (level > 0) {
                        card.append($('<div class="level-badge">LEVEL ' + level + '</div>'));
                    }

                    // Title with link
                    const titleDiv = $('<div class="aiopms-grid-title"></div>');
                    if (page.a_attr && page.a_attr.href) {
                        titleDiv.append($('<a target="_blank"></a>')
                            .attr('href', page.a_attr.href)
                            .attr('title', page.a_attr.title || 'View page')
                            .append($('<strong></strong>').text(page.text))
                        );
                    } else {
                        titleDiv.append($('<strong></strong>').text(page.text));
                    }

                    // Status badge
                    let statusBadge = '<span class="aiopms-grid-status">📄 Page</span>';
                    if (page.meta && page.meta.status) {
                        switch(page.meta.status) {
                            case 'publish':
                                statusBadge = '<span class="aiopms-grid-status status-published">🟢 Published</span>';
                                break;
                            case 'draft':
                                statusBadge = '<span class="aiopms-grid-status status-draft">🟡 Draft</span>';
                                break;
                            case 'pending':
                                statusBadge = '<span class="aiopms-grid-status status-pending">🟠 Pending</span>';
                                break;
                            case 'private':
                                statusBadge = '<span class="aiopms-grid-status status-private">🔵 Private</span>';
                                break;
                            case 'trash':
                                statusBadge = '<span class="aiopms-grid-status status-trash">🔴 Trash</span>';
                                break;
                        }
                    }

                    const header = $('<div class="aiopms-grid-header"></div>')
                        .append(titleDiv)
                        .append(statusBadge);

                    card.append(header);

                    // Add meta information
                    if (page.meta) {
                        const metaInfo = $('<div class="aiopms-grid-meta"></div>');

                        if (page.meta.description) {
                            metaInfo.append('<div class="aiopms-grid-excerpt">📝 ' + page.meta.description + '</div>');
                        }

                        const details = $('<div class="aiopms-grid-details"></div>');

                        if (page.meta.published) {
                            details.append('<span class="aiopms-meta-item">📅 ' + page.meta.published + '</span>');
                        }

                        if (page.meta.modified && page.meta.modified !== page.meta.published) {
                            details.append('<span class="aiopms-meta-item">✏️ ' + page.meta.modified + '</span>');
                        }

                        if (page.meta.author) {
                            details.append('<span class="aiopms-meta-item">👤 ' + page.meta.author + '</span>');
                        }

                        if (details.children().length > 0) {
                            metaInfo.append(details);
                        }

                        card.append(metaInfo);
                    }

                    return card;
                }

                // Function to add children recursively
                function addChildren(parentCard, children, level = 1) {
                    if (!children || children.length === 0) return;

                    const childrenContainer = $('<div class="aiopms-grid-children"></div>');

                    children.forEach(child => {
                        const childCard = createGridCard(child, level);

                        // Add grandchildren if they exist
                        const grandchildren = pagesByParent[child.id];
                        if (grandchildren && grandchildren.length > 0) {
                            addChildren(childCard, grandchildren, level + 1);
                        }

                        childrenContainer.append(childCard);
                    });

                    parentCard.append(childrenContainer);
                }

                // Sort root pages with homepage first, then alphabetically
                rootPages.sort((a, b) => {
                    // Check if either page is the homepage
                    const aIsHomepage = a.meta && a.meta.is_homepage;
                    const bIsHomepage = b.meta && b.meta.is_homepage;
                    
                    // Homepage always comes first
                    if (aIsHomepage && !bIsHomepage) return -1;
                    if (!aIsHomepage && bIsHomepage) return 1;
                    
                    // If both are homepage or neither is homepage, sort alphabetically
                    return a.text.localeCompare(b.text);
                });

                // Create cards for root pages
                rootPages.forEach(rootPage => {
                    const rootCard = createGridCard(rootPage, 0);

                    // Add children pages
                    const children = pagesByParent[rootPage.id];
                    if (children && children.length > 0) {
                        addChildren(rootCard, children);
                    }

                    container.append(rootCard);
                });

                console.log('Grid View: Successfully rendered ' + rootPages.length + ' root pages');

                // Add click handlers for expand/collapse
                container.on('click', '.aiopms-grid-card:has(> .aiopms-grid-children)', function(e) {
                    // Don't trigger if clicking on links
                    if ($(e.target).closest('a').length) return;

                    e.stopPropagation();

                    const card = $(this);
                    const children = card.find('> .aiopms-grid-children');
                    const isExpanded = children.hasClass('expanded');

                    // Toggle with smooth animation
                    if (isExpanded) {
                        children.removeClass('expanded');
                        card.removeClass('has-expanded-children');
                    } else {
                        children.addClass('expanded');
                        card.addClass('has-expanded-children');
                    }

                    // Add visual feedback
                    card.addClass('clicked');
                    setTimeout(() => card.removeClass('clicked'), 200);
                });

                // Add hover effects for better UX
                container.on('mouseenter', '.aiopms-grid-card:has(> .aiopms-grid-children)', function() {
                    $(this).addClass('hover-expandable');
                }).on('mouseleave', '.aiopms-grid-card:has(> .aiopms-grid-children)', function() {
                    $(this).removeClass('hover-expandable');
                });

                // Add tooltip and child count for expandable cards
                container.find('.aiopms-grid-card:has(> .aiopms-grid-children)').each(function() {
                    const card = $(this);
                    const children = card.find('> .aiopms-grid-children');
                    const childCount = children.find('.aiopms-grid-card').length;
                    
                    // Add child count to title
                    const titleDiv = card.find('.aiopms-grid-title');
                    titleDiv.attr('data-child-count', childCount);
                    
                    // Add tooltip
                    card.attr('title', `Click to expand/collapse ${childCount} child page${childCount !== 1 ? 's' : ''}`);
                });

            } catch (error) {
                console.error('Grid View Error:', error);
                container.html('<div class="abpcwa-error">Error rendering Grid View: ' + error.message + '</div>');
            }
        }, 100);
    }

    // 4. Event Handlers
    function setupEventHandlers() {
        console.log('AIOPMS: Setting up event handlers');
        
        // View switcher buttons
        $('.abpcwa-view-controls').on('click', '.button', function() {
            const view = $(this).data('view');
            console.log('AIOPMS: View button clicked:', view);
            if (view !== currentView) {
                switchView(view);
            }
        });


        // Search functionality
        $('#abpcwa-hierarchy-search').keyup(function() {
            var to = false;
            if (to) clearTimeout(to);
            to = setTimeout(function() {
                var v = $('#abpcwa-hierarchy-search').val();
                if (currentView === 'tree') {
                    $('#abpcwa-hierarchy-tree').jstree(true).search(v);
                }
                // Add search logic for other views here
            }, 250);
        });

    }

    // 5. Copy hierarchy to clipboard
    function copyHierarchyToClipboard() {
        if (!hierarchyData) {
            alert('Hierarchy data not loaded yet.');
            return;
        }

        let textToCopy = '';
        const tree = $('#abpcwa-hierarchy-tree').jstree(true);
        const rootNode = tree.get_node('#');

        function traverse(node, level) {
            const prefix = '    '.repeat(level);
            const nodeText = node.text;

            textToCopy += `${prefix}${nodeText}\n`;

            if (node.children) {
                node.children.forEach(childId => {
                    const childNode = tree.get_node(childId);
                    traverse(childNode, level + 1);
                });
            }
        }

        rootNode.children.forEach(rootChildId => {
            const rootChildNode = tree.get_node(rootChildId);
            traverse(rootChildNode, 0);
        });

        navigator.clipboard.writeText(textToCopy).then(() => {
            alert('Hierarchy copied to clipboard!');
        }, () => {
            alert('Failed to copy hierarchy.');
        });
    }

    // 6. Export hierarchy
    function exportHierarchy(format) {
        if (!hierarchyData) {
            alert('Hierarchy data not loaded yet.');
            return;
        }

        const exportUrl = aiopmsHierarchy.rest_url + `hierarchy/export/${format}` + `?_wpnonce=${aiopmsHierarchy.nonce}`;
        window.open(exportUrl, '_blank');
    }

    // 5. Handle window resize for responsive visualizations
    let resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (hierarchyData && currentView !== 'tree') {
                // Re-render the current view on resize for proper responsiveness
                switchView(currentView);
            }
        }, 250);
    });

    // 6. Add keyboard navigation support
    $(document).on('keydown', function(e) {
        // Escape key to reset zoom
        if (e.key === 'Escape' && currentView === 'orgchart') {
            const container = $('#abpcwa-hierarchy-' + currentView);
            const svg = d3.select(container.find('svg').get(0));
            if (svg && svg.node()) {
                svg.transition().duration(750).call(
                    d3.zoom().transform,
                    d3.zoomIdentity
                );
            }
        }
    });

    // 7. Add manual zoom controls for better UX
    function addZoomControls(container) {
        const controls = $('<div class="d3-zoom-controls"></div>');
        controls.append('<button title="Zoom In" data-action="zoom-in">+</button>');
        controls.append('<button title="Zoom Out" data-action="zoom-out">-</button>');
        controls.append('<button title="Reset Zoom" data-action="reset">↺</button>');
        
        container.append(controls);

        controls.on('click', 'button', function() {
            const action = $(this).data('action');
            const svg = d3.select(container.find('svg').get(0));
            const currentTransform = d3.zoomTransform(svg.node());
            
            switch (action) {
                case 'zoom-in':
                    svg.transition().duration(250).call(
                        d3.zoom().scaleBy,
                        1.5
                    );
                    break;
                case 'zoom-out':
                    svg.transition().duration(250).call(
                        d3.zoom().scaleBy,
                        0.75
                    );
                    break;
                case 'reset':
                    svg.transition().duration(750).call(
                        d3.zoom().transform,
                        d3.zoomIdentity
                    );
                    break;
            }
        });
    }

    // 8. Initialize the script
    console.log('AIOPMS: Starting hierarchy initialization');
    initHierarchy();
    setupEventHandlers();
    console.log('AIOPMS: Hierarchy initialization complete');
});
