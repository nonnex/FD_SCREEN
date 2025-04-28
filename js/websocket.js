let ws;

async function connectWebSocket() {
    // In offline mode, skip WebSocket connection and render LocalStorage data directly
    if (typeof APP_MODE !== 'undefined' && APP_MODE === 'offline') {
        console.log('Offline mode: Skipping WebSocket connection, rendering LocalStorage data...');
        // Clear existing UI
        document.querySelectorAll('.drag-inner-list').forEach(column => {
            column.innerHTML = '';
        });

        // Load orders (now async)
        const { orders, virtualOrders } = await window.utils.loadOrders();

        // Render orders and virtual orders from LocalStorage
        for (const order of Object.values(orders)) {
            await handleOrderCreated(order);
        }
        for (const event of Object.values(virtualOrders)) {
            if (event.AuftragId.startsWith('E_')) {
                await handleEventCreated(event);
            } else {
                await handleOrderCreated(event); // For V_99999
            }
        }

        // Apply LocalStorage states after rendering
        const { orders: updatedOrders, virtualOrders: updatedVirtualOrders } = await window.utils.loadOrders();
        render.applyOrders(updatedOrders, false);
        render.applyOrders(updatedVirtualOrders, true);
        return;
    }

    // Online mode: Connect to WebSocket
    ws = new WebSocket(WEBSOCKET_URI); // Use the URI from config.php
    
    ws.onopen = function() {
        console.log('Connected to WebSocket server at ' + WEBSOCKET_URI);
    };

    ws.onmessage = async function(event) {
        try {
            const message = JSON.parse(event.data);
            console.log('Received WebSocket message:', message);
    
            // Handle case where message is an array (malformed initial_state)
            let messagesToProcess = [];
            if (Array.isArray(message)) {
                console.warn('Received malformed message as array, treating as events:', message);
                messagesToProcess = message; // Treat the array as a list of messages
            } else if (message.type === 'initial_state') {
                document.querySelectorAll('.drag-inner-list').forEach(column => {
                    column.innerHTML = '';
                });
    
                if (message.data.orders) {
                    for (const orderMsg of message.data.orders) {
                        if (orderMsg.type === 'order_created') {
                            await handleOrderCreated(orderMsg.data);
                        }
                    }
                }
    
                if (message.data.events) {
                    for (const eventMsg of message.data.events) {
                        if (eventMsg.type === 'event_created') {
                            await handleEventCreated(eventMsg.data);
                        }
                    }
                }
    
                const { orders, virtualOrders } = await window.utils.loadOrders();
                render.applyOrders(orders, false);
                render.applyOrders(virtualOrders, true);
                return; // Exit after handling initial_state
            } else {
                messagesToProcess = [message]; // Single message
            }
    
            // Process individual messages (e.g., array of events or single message)
            for (const msg of messagesToProcess) {
                if (msg.type === 'order_created') {
                    await handleOrderCreated(msg.data);
                } else if (msg.type === 'event_created') {
                    await handleEventCreated(msg.data);
                } else if (msg.type === 'order_updated') {
                    await handleOrderUpdated(msg.data);
                } else if (msg.type === 'order_deleted') {
                    await handleOrderDeleted(msg.data);
                } else if (msg.type === 'event_updated') {
                    await handleEventUpdated(msg.data);
                } else if (msg.type === 'event_deleted') {
                    await handleEventDeleted(msg.data);
                } else {
                    console.warn('Unknown message type:', msg.type);
                }
            }
        } catch (e) {
            console.error('Failed to parse WebSocket message:', event.data, e);
        }
    };

    ws.onclose = function() {
        console.log('WebSocket connection closed. Reconnecting...');
        setTimeout(connectWebSocket, 5000);
    };

    ws.onerror = function(error) {
        console.error('WebSocket error:', error);
    };
}

async function handleOrderCreated(order) {
    console.log('Handling order creation:', order);
    const column = document.getElementById(`column-${order.Status}`);
    if (!column) {
        console.warn(`Column not found for status ${order.Status}`);
        return;
    }

    const existingOrder = document.getElementById(order.AuftragId);
    if (existingOrder) {
        console.log(`Order ${order.AuftragId} already exists, updating instead`);
        await handleOrderUpdated(order);
        return;
    }

    const auftragId = String(order.AuftragId);
    const isVirtual = auftragId.startsWith('V_') || auftragId.startsWith('E_');
    const html = render.renderItem(order, isVirtual);
    column.insertAdjacentHTML('beforeend', html);
    console.log(`Added order ${order.AuftragId} to column ${order.Status}`);
    utils.sortColumn(column);

    window.ordersState = await utils.saveOrder(order, isVirtual);
}

async function handleOrderUpdated(order) {
    const orderEl = document.getElementById(order.AuftragId);
    if (!orderEl) {
        console.log(`Order ${order.AuftragId} not found, creating instead`);
        await handleOrderCreated(order);
        return;
    }

    const currentColumnId = orderEl.parentNode?.id;
    const targetColumnId = `column-${order.Status}`;
    if (currentColumnId !== targetColumnId) {
        const targetColumn = document.getElementById(targetColumnId);
        if (targetColumn && orderEl.parentNode !== targetColumn) {
            targetColumn.appendChild(orderEl);
            console.log(`Moved order ${order.AuftragId} to column ${targetColumnId}`);
            utils.sortColumn(targetColumn);
            if (currentColumnId) {
                utils.sortColumn(document.getElementById(currentColumnId));
            }
        }
    }

    // Update visuals
    orderEl.dataset.status = order.Status;
    const colorBar = orderEl.querySelector('div[style*="background-color"]');
    if (colorBar) {
        colorBar.style.backgroundColor = `#${utils.colorMapping[order.Status]}`;
    }
    const deliveryButton = orderEl.querySelector('.delivery-button');
    if (deliveryButton) {
        const newIcon = utils.iconMapping[order.Tags[0]?.lTagId] || 'neu.svg';
        deliveryButton.src = `img/UI/${newIcon}`;
    }

    // Update LocalStorage
    window.ordersState = await utils.saveOrder(order, order.AuftragId.startsWith('V_') || order.AuftragId.startsWith('E_'));
    console.log(`Updated order ${order.AuftragId}`);
}

async function handleOrderDeleted(data) {
    const orderEl = document.getElementById(data.AuftragId);
    if (orderEl) {
        const column = orderEl.parentNode;
        orderEl.remove();
        console.log(`Removed order ${data.AuftragId}`);
        utils.sortColumn(column);
        window.ordersState = await utils.deleteOrder(data.AuftragId, false);
    }
}

async function handleEventCreated(event) {
    const column = document.getElementById('column-1'); // Events go in NEU column
    if (!column) {
        console.warn(`Column not found for events (status 1)`);
        return;
    }

    if (document.getElementById(event.AuftragId)) {
        console.log(`Event ${event.AuftragId} already exists, updating instead`);
        await handleEventUpdated(event);
        return;
    }

    const html = render.renderItem(event, true);
    column.insertAdjacentHTML('beforeend', html);
    console.log(`Added event ${event.AuftragId} to column 1`);
    utils.sortColumn(column);

    // Update LocalStorage
    window.ordersState = await utils.saveOrder(event, true);
}

async function handleEventUpdated(event) {
    const eventEl = document.getElementById(event.AuftragId);
    if (!eventEl) {
        console.log(`Event ${event.AuftragId} not found, creating instead`);
        await handleEventCreated(event);
        return;
    }

    // Events stay in column 1, just update visuals
    const currentColumnId = eventEl.parentNode?.id;
    const targetColumnId = 'column-1';
    if (currentColumnId !== targetColumnId) {
        const targetColumn = document.getElementById(targetColumnId);
        if (targetColumn && eventEl.parentNode !== targetColumn) {
            targetColumn.appendChild(eventEl);
            console.log(`Moved event ${event.AuftragId} to column ${targetColumnId}`);
            utils.sortColumn(targetColumn);
            if (currentColumnId) {
                utils.sortColumn(document.getElementById(currentColumnId));
            }
        }
    }

    eventEl.dataset.status = event.Status;
    const colorBar = eventEl.querySelector('div[style*="background-color"]');
    if (colorBar) {
        colorBar.style.backgroundColor = `#${utils.colorMapping[event.Status]}`;
    }

    // Update LocalStorage
    window.ordersState = await utils.saveOrder(event, true);
    console.log(`Updated event ${event.AuftragId}`);
}

async function handleEventDeleted(data) {
    const eventEl = document.getElementById(data.AuftragId);
    if (eventEl) {
        const column = eventEl.parentNode;
        eventEl.remove();
        console.log(`Removed event ${data.AuftragId}`);
        utils.sortColumn(column);
        window.ordersState = await utils.deleteOrder(data.AuftragId, true);
    }
}

// Clean up WebSocket connection on page unload
window.onbeforeunload = function () {
    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.close();
    }
};

window.websocket = {
    connectWebSocket,
    handleOrderCreated,
    handleOrderUpdated,
    handleOrderDeleted,
    handleEventCreated,
    handleEventUpdated,
    handleEventDeleted
};