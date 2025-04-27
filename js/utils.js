const tagMapping = {
    1: { lTagId: 4, szName: 'Neu' },
    2: { lTagId: 2, szName: 'Produktion' },
    3: { lTagId: 5, szName: 'Versandbereit' },
    4: { lTagId: 1, szName: 'Versendet' },
    5: { lTagId: 6, szName: 'Fakturieren' }
};

const colorMapping = {
    1: 'fb7d44',
    2: '2a92bf',
    3: 'f4ce46',
    4: '00b961',
    5: '00b961'
};

const iconMapping = {
    4: 'neu.svg',
    2: 'inprod.svg',
    5: 'vorb.svg',
    1: 'delivery_0.svg',
    6: 'fakturieren.svg'
};

function parseDeliveryDate(dateStr) {
    if (!dateStr) return null;
    let date;
    if (dateStr.includes('.')) {
        const parts = dateStr.split('.');
        if (parts.length !== 3) return null;
        const day = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const year = parts[2].length === 2 ? 2000 + parseInt(parts[2], 10) : parseInt(parts[2], 10);
        date = new Date(year, month, day);
    } else {
        date = new Date(dateStr);
    }
    // Format to DD.MM.YYYY
    if (date) {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}.${month}.${year}`;
    }
    return null;
}

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
        const [dayA, monthA, yearA] = dateA.split('.').map(Number);
        const [dayB, monthB, yearB] = dateB.split('.').map(Number);
        return new Date(yearA, monthA - 1, dayA) - new Date(yearB, monthB - 1, dayB);
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

// IndexedDB Setup
const dbName = "OrdersDB";
const dbVersion = 1;
let db;

function initDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(dbName, dbVersion);

        request.onupgradeneeded = function(event) {
            const db = event.target.result;
            db.createObjectStore("orders", { keyPath: "AuftragId" });
            db.createObjectStore("virtual_orders", { keyPath: "AuftragId" });
        };

        request.onsuccess = function(event) {
            db = event.target.result;
            resolve(db);
        };

        request.onerror = function(event) {
            console.error("IndexedDB error:", event.target.error);
            reject(event.target.error);
        };
    });
}

async function loadOrders() {
    if (!db) {
        await initDB();
    }

    const transaction = db.transaction(["orders", "virtual_orders"], "readonly");
    const ordersStore = transaction.objectStore("orders");
    const virtualOrdersStore = transaction.objectStore("virtual_orders");

    const getOrders = new Promise((resolve) => {
        const request = ordersStore.getAll();
        request.onsuccess = () => {
            const orders = {};
            request.result.forEach(order => {
                orders[order.AuftragId] = order;
            });
            resolve(orders);
        };
        request.onerror = () => resolve({});
    });

    const getVirtualOrders = new Promise((resolve) => {
        const request = virtualOrdersStore.getAll();
        request.onsuccess = () => {
            const virtualOrders = {};
            request.result.forEach(order => {
                virtualOrders[order.AuftragId] = order;
            });
            resolve(virtualOrders);
        };
        request.onerror = () => resolve({});
    });

    let orders = await getOrders;
    let virtualOrders = await getVirtualOrders;

    // Offline-Mock-Daten laden, falls keine Daten vorhanden sind
    if (typeof APP_MODE !== 'undefined' && APP_MODE === 'offline' && Object.keys(orders).length === 0 && Object.keys(virtualOrders).length === 0) {
        console.log('Initializing mock data for offline mode from JSON files...');

        try {
            const [ordersResponse, eventsResponse, virtualOrdersResponse] = await Promise.all([
                fetch('mock_data/orders.json'),
                fetch('mock_data/events.json'),
                fetch('mock_data/virtual_orders.json')
            ]);

            const mockOrders = await ordersResponse.json();
            const mockEvents = await eventsResponse.json();
            const mockVirtualOrders = await virtualOrdersResponse.json();

            const transaction = db.transaction(["orders", "virtual_orders"], "readwrite");
            const ordersStore = transaction.objectStore("orders");
            const virtualOrdersStore = transaction.objectStore("virtual_orders");

            mockOrders.forEach(msg => {
                if (msg.type === 'order_created') {
                    ordersStore.put(msg.data);
                    orders[msg.data.AuftragId] = msg.data;
                }
            });

            mockEvents.forEach(msg => {
                if (msg.type === 'event_created') {
                    virtualOrdersStore.put(msg.data);
                    virtualOrders[msg.data.AuftragId] = msg.data;
                }
            });

            mockVirtualOrders.forEach(msg => {
                if (msg.type === 'order_created') {
                    virtualOrdersStore.put(msg.data);
                    virtualOrders[msg.data.AuftragId] = msg.data;
                }
            });

            console.log('Mock data initialized in IndexedDB for offline mode:', { orders, virtualOrders });
        } catch (error) {
            console.error('Failed to load mock data from JSON files:', error);
        }
    }

    return { orders, virtualOrders };
}

async function saveOrder(order, isVirtual = false) {
    if (!db) {
        await initDB();
    }

    const transaction = db.transaction(["orders", "virtual_orders"], "readwrite");
    const store = isVirtual ? transaction.objectStore("virtual_orders") : transaction.objectStore("orders");

    return new Promise((resolve, reject) => {
        const request = store.put(order);
        request.onsuccess = async () => {
            console.log(`Saved ${isVirtual ? 'virtual' : 'real'} order ${order.AuftragId} to IndexedDB`);
            const { orders, virtualOrders } = await loadOrders();
            resolve({ orders, virtualOrders });
        };
        request.onerror = () => {
            console.error(`Failed to save order ${order.AuftragId} to IndexedDB`);
            reject(request.error);
        };
    });
}

async function deleteOrder(auftragId, isVirtual = false) {
    if (!db) {
        await initDB();
    }

    const transaction = db.transaction(["orders", "virtual_orders"], "readwrite");
    const store = isVirtual ? transaction.objectStore("virtual_orders") : transaction.objectStore("orders");

    return new Promise((resolve, reject) => {
        const request = store.delete(auftragId);
        request.onsuccess = async () => {
            console.log(`Deleted ${isVirtual ? 'virtual' : 'real'} order ${auftragId} from IndexedDB`);
            const { orders, virtualOrders } = await loadOrders();
            resolve({ orders, virtualOrders });
        };
        request.onerror = () => {
            console.error(`Failed to delete order ${auftragId} from IndexedDB`);
            reject(request.error);
        };
    });
}

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