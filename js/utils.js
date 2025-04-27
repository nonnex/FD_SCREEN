const tagMapping = {
    1: { lTagId: 4, szName: 'Neu' },
    2: { lTagId: 2, szName: 'Produktion' },
    3: { lTagId: 5, szName: 'Versandbereit' },
    4: { lTagId: 1, szName: 'Versendet' },
    5: { lTagId: 6, szName: 'Fakturieren' }
};

const colorMapping = {
    1: 'fb7d44', // Neu (Orange)
    2: '2a92bf', // Produktion (Blue)
    3: 'f4ce46', // Versandbereit (Yellow)
    4: '00b961', // Versendet (Green)
    5: '00b961'  // Fakturieren (Green)
};

const iconMapping = {
    4: 'neu.svg',         // Neu
    2: 'inprod.svg',      // Produktion
    5: 'vorb.svg',        // Versandbereit
    1: 'delivery_0.svg',  // Versendet
    6: 'fakturieren.svg'  // Fakturieren
};

// Function to parse delivery date from DOM or data (format: DD.MM.YY or YYYY-MM-DD)
function parseDeliveryDate(dateStr) {
    if (!dateStr) return null;
    if (dateStr.includes('.')) {
        const parts = dateStr.split('.');
        if (parts.length !== 3) return null;
        const day = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const year = 2000 + parseInt(parts[2], 10);
        return new Date(year, month, day);
    } else {
        return new Date(dateStr);
    }
}

// Function to sort orders within a column by delivery date (ascending)
function sortColumn(column) {
    const columnId = column.id;
    const items = Array.from(column.children)
        .filter(el => el.id && el.classList.contains('no-drag'));

    let v99999 = null;
    const otherItems = [];
    if (columnId === 'column-2') {
        items.forEach(item => {
            if (item.id === 'V_99999') {
                v99999 = item;
            } else {
                otherItems.push(item);
            }
        });
    } else {
        otherItems.push(...items);
    }

    otherItems.sort((a, b) => {
        const dateA = parseDeliveryDate(a.querySelector('.table-cell-liefertermin')?.textContent.trim());
        const dateB = parseDeliveryDate(b.querySelector('.table-cell-liefertermin')?.textContent.trim());
        if (!dateA || !dateB) return 0;
        return dateA - dateB;
    });

    while (column.firstChild) {
        column.removeChild(column.firstChild);
    }
    otherItems.forEach(item => column.appendChild(item));
    if (v99999) {
        column.appendChild(v99999);
        console.log('Pinned V_99999 to bottom of Production column');
    }

    console.log(`Sorted column ${columnId} by delivery date (ascending)`);
}

// LocalStorage helpers
async function loadOrders() {
    let orders = JSON.parse(localStorage.getItem('orders') || '{}');
    let virtualOrders = JSON.parse(localStorage.getItem('virtual_orders') || '{}');

    function flattenOrders(orders, prefix = '') {
        // Check if the orders object is malformed (e.g., contains order properties as keys)
        const sampleKey = Object.keys(orders)[0];
        if (sampleKey && typeof orders[sampleKey] === 'object' && orders[sampleKey].AuftragId) {
            // Already in correct format: { "V_99999": {...}, "E_100000": {...} }
            return orders;
        }
        // If the structure is incorrect (e.g., { "AuftragId": "V_99999", ... }), fix it
        if (orders.AuftragId) {
            const auftragId = orders.AuftragId;
            console.log(`Fixing malformed LocalStorage under ${prefix}${auftragId}:`, orders);
            return { [auftragId]: orders };
        }
        return orders;
    }

    orders = flattenOrders(orders);
    virtualOrders = flattenOrders(virtualOrders, 'V_');

    // Clean up invalid entries
    Object.keys(orders).forEach(key => {
        if (!key || !orders[key] || !orders[key].AuftragId || !orders[key].AuftragsKennung) {
            console.warn(`Removing invalid LocalStorage key (orders): ${key}, Data:`, orders[key]);
            delete orders[key];
        }
    });
    Object.keys(virtualOrders).forEach(key => {
        if (!key || !virtualOrders[key] || !virtualOrders[key].AuftragId || !virtualOrders[key].AuftragsKennung) {
            console.warn(`Removing invalid LocalStorage key (virtual_orders): ${key}, Data:`, virtualOrders[key]);
            delete virtualOrders[key];
        }
    });

    // Initialize mock data in offline mode if LocalStorage is empty
    if (typeof APP_MODE !== 'undefined' && APP_MODE === 'offline' && Object.keys(orders).length === 0 && Object.keys(virtualOrders).length === 0) {
        console.log('Initializing mock data for offline mode from JSON files...');

        try {
            // Fetch mock data from JSON files
            const [ordersResponse, eventsResponse, virtualOrdersResponse] = await Promise.all([
                fetch('mock_data/orders.json'),
                fetch('mock_data/events.json'),
                fetch('mock_data/virtual_orders.json')
            ]);

            const mockOrders = await ordersResponse.json();
            const mockEvents = await eventsResponse.json();
            const mockVirtualOrders = await virtualOrdersResponse.json();

            // Process mock orders
            mockOrders.forEach(msg => {
                if (msg.type === 'order_created') {
                    orders[msg.data.AuftragId] = msg.data;
                }
            });

            // Process mock events and virtual orders
            mockEvents.forEach(msg => {
                if (msg.type === 'event_created') {
                    virtualOrders[msg.data.AuftragId] = msg.data;
                }
            });
            mockVirtualOrders.forEach(msg => {
                if (msg.type === 'order_created') {
                    virtualOrders[msg.data.AuftragId] = msg.data;
                }
            });

            // Save to LocalStorage
            localStorage.setItem('orders', JSON.stringify(orders));
            localStorage.setItem('virtual_orders', JSON.stringify(virtualOrders));
            console.log('Mock data initialized in LocalStorage for offline mode:', { orders, virtualOrders });
        } catch (error) {
            console.error('Failed to load mock data from JSON files:', error);
        }
    }

    return { orders, virtualOrders };
}

function saveOrder(order, isVirtual = false) {
    // Since loadOrders is async, we need to handle it properly
    return loadOrders().then(({ orders, virtualOrders }) => {
        const targetOrders = isVirtual ? virtualOrders : orders;
        targetOrders[order.AuftragId] = order;
        localStorage.setItem(isVirtual ? 'virtual_orders' : 'orders', JSON.stringify(targetOrders));
        console.log(`Saved ${isVirtual ? 'virtual' : 'real'} order ${order.AuftragId} to LocalStorage`);
        return { orders, virtualOrders };
    });
}

function deleteOrder(auftragId, isVirtual = false) {
    // Since loadOrders is async, we need to handle it properly
    return loadOrders().then(({ orders, virtualOrders }) => {
        const targetOrders = isVirtual ? virtualOrders : orders;
        delete targetOrders[auftragId];
        localStorage.setItem(isVirtual ? 'virtual_orders' : 'orders', JSON.stringify(targetOrders));
        console.log(`Deleted ${isVirtual ? 'virtual' : 'real'} order ${auftragId} from LocalStorage`);
        return { orders, virtualOrders };
    });
}

// Export for use in other modules (if using modules, otherwise these are global)
window.utils = {
    tagMapping,
    colorMapping,
    iconMapping,
    parseDeliveryDate,
    sortColumn,
    loadOrders,
    saveOrder,
    deleteOrder
};