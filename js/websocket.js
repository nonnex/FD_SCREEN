let ws;

function connectWebSocket() {
    ws = new WebSocket(WEBSOCKET_URI); // Use the URI from config.php
    
    ws.onopen = function() {
        console.log('Connected to WebSocket server at ' + WEBSOCKET_URI);
    };

    ws.onmessage = function(event) {
        try {
            const message = JSON.parse(event.data);
            console.log('Received WebSocket message:', message);

            if (message.type === 'initial_state') {
                // Clear existing UI
                document.querySelectorAll('.drag-inner-list').forEach(column => {
                    column.innerHTML = '';
                });

                // Process initial orders
                if (message.data.orders) {
                    message.data.orders.forEach(orderMsg => {
                        if (orderMsg.type === 'order_created') {
                            handleOrderCreated(orderMsg.data);
                        }
                    });
                }

                // Process initial events
                if (message.data.events) {
                    message.data.events.forEach(eventMsg => {
                        if (eventMsg.type === 'event_created') {
                            handleEventCreated(eventMsg.data);
                        }
                    });
                }

                // Apply LocalStorage states after rendering
                render.applyOrders(window.ordersState.orders, false);
                render.applyOrders(window.ordersState.virtualOrders, true);
            } else if (message.type === 'order_created') {
                handleOrderCreated(message.data);
            } else if (message.type === 'order_updated') {
                handleOrderUpdated(message.data);
            } else if (message.type === 'order_deleted') {
                handleOrderDeleted(message.data);
            } else if (message.type === 'event_created') {
                handleEventCreated(message.data);
            } else if (message.type === 'event_updated') {
                handleEventUpdated(message.data);
            } else if (message.type === 'event_deleted') {
                handleEventDeleted(message.data);
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

function handleOrderCreated(order) {
    const column = document.getElementById(`column-${order.Status}`);
    if (!column) {
        console.warn(`Column not found for status ${order.Status}`);
        return;
    }

    // Check if order already exists
    const existingOrder = document.getElementById(order.AuftragId);
    if (existingOrder) {
        console.log(`Order ${order.AuftragId} already exists, updating instead`);
        handleOrderUpdated(order);
        return;
    }

    const html = render.renderItem(order, false);
    column.insertAdjacentHTML('beforeend', html);
    console.log(`Added order ${order.AuftragId} to column ${order.Status}`);
    utils.sortColumn(column);

    // Update LocalStorage
    window.ordersState = utils.saveOrder(order, false);
}

function handleOrderUpdated(order) {
    const orderEl = document.getElementById(order.AuftragId);
    if (!orderEl) {
        console.log(`Order ${order.AuftragId} not found, creating instead`);
        handleOrderCreated(order);
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
    window.ordersState = utils.saveOrder(order, false);
    console.log(`Updated order ${order.AuftragId}`);
}

function handleOrderDeleted(data) {
    const orderEl = document.getElementById(data.AuftragId);
    if (orderEl) {
        const column = orderEl.parentNode;
        orderEl.remove();
        console.log(`Removed order ${data.AuftragId}`);
        utils.sortColumn(column);
        window.ordersState = utils.deleteOrder(data.AuftragId, false);
    }
}

function handleEventCreated(event) {
    const column = document.getElementById('column-1'); // Events go in NEU column
    if (!column) {
        console.warn(`Column not found for events (status 1)`);
        return;
    }

    if (document.getElementById(event.AuftragId)) {
        console.log(`Event ${event.AuftragId} already exists, updating instead`);
        handleEventUpdated(event);
        return;
    }

    const html = render.renderItem(event, true);
    column.insertAdjacentHTML('beforeend', html);
    console.log(`Added event ${event.AuftragId} to column 1`);
    utils.sortColumn(column);

    // Update LocalStorage
    window.ordersState = utils.saveOrder(event, true);
}

function handleEventUpdated(event) {
    const eventEl = document.getElementById(event.AuftragId);
    if (!eventEl) {
        console.log(`Event ${event.AuftragId} not found, creating instead`);
        handleEventCreated(event);
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
    window.ordersState = utils.saveOrder(event, true);
    console.log(`Updated event ${event.AuftragId}`);
}

function handleEventDeleted(data) {
    const eventEl = document.getElementById(data.AuftragId);
    if (eventEl) {
        const column = eventEl.parentNode;
        eventEl.remove();
        console.log(`Removed event ${data.AuftragId}`);
        utils.sortColumn(column);
        window.ordersState = utils.deleteOrder(data.AuftragId, true);
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