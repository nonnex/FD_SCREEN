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
function loadOrders() {
    let orders = JSON.parse(localStorage.getItem('orders') || '{}');
    let virtualOrders = JSON.parse(localStorage.getItem('virtual_orders') || '{}');

    function flattenOrders(orders, prefix = '') {
        if (orders[prefix + '99999'] && Object.keys(orders[prefix + '99999']).some(key => key !== 'Status' && key !== 'AuftragsKennung' && key !== 'Tags' && key !== 'ShowPos')) {
            console.log(`Flattening nested LocalStorage under ${prefix}99999:`, orders[prefix + '99999']);
            return orders[prefix + '99999'];
        } else if (orders['0'] && Object.keys(orders['0']).some(key => key !== 'Status' && key !== 'AuftragsKennung' && key !== 'Tags' && key !== 'ShowPos')) {
            console.log('Flattening nested LocalStorage under 0:', orders['0']);
            return orders['0'];
        }
        return orders;
    }

    orders = flattenOrders(orders);
    virtualOrders = flattenOrders(virtualOrders, 'V_');

    Object.keys(orders).forEach(key => {
        if (key === '0' || !key || isNaN(key) || !orders[key].AuftragsKennung) {
            console.warn(`Removing invalid LocalStorage key (orders): ${key}, Data:`, orders[key]);
            delete orders[key];
        }
    });
    Object.keys(virtualOrders).forEach(key => {
        if (!key || !virtualOrders[key].AuftragsKennung) {
            console.warn(`Removing invalid LocalStorage key (virtual_orders): ${key}, Data:`, virtualOrders[key]);
            delete virtualOrders[key];
        }
    });

    return { orders, virtualOrders };
}

function saveOrder(order, isVirtual = false) {
    const { orders, virtualOrders } = loadOrders();
    const targetOrders = isVirtual ? virtualOrders : orders;
    targetOrders[order.AuftragId] = order;
    localStorage.setItem(isVirtual ? 'virtual_orders' : 'orders', JSON.stringify(targetOrders));
    console.log(`Saved ${isVirtual ? 'virtual' : 'real'} order ${order.AuftragId} to LocalStorage`);
    return { orders, virtualOrders };
}

function deleteOrder(auftragId, isVirtual = false) {
    const { orders, virtualOrders } = loadOrders();
    const targetOrders = isVirtual ? virtualOrders : orders;
    delete targetOrders[auftragId];
    localStorage.setItem(isVirtual ? 'virtual_orders' : 'orders', JSON.stringify(targetOrders));
    console.log(`Deleted ${isVirtual ? 'virtual' : 'real'} order ${auftragId} from LocalStorage`);
    return { orders, virtualOrders };
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