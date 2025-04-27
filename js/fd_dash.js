$(function() {
    // Initialize LocalStorage
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

    localStorage.setItem('orders', JSON.stringify(orders));
    localStorage.setItem('virtual_orders', JSON.stringify(virtualOrders));
    console.log('Cleaned LocalStorage (orders):', orders);
    console.log('Cleaned LocalStorage (virtual_orders):', virtualOrders);

    // Tag, color, and icon mappings
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
        if (columnId === '2') {
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

    // Function to render an order or event (ported from GetOrderContainer and Print_Events)
    function renderItem(item, isEvent = false) {
        const { AuftragId, AuftragsNr, AuftragsKennung, Datum_Erfassung, BestellNr, Liefertermin, KundenNr, KundenMatchcode, Status, ShowPos, Tags, Positionen } = item;

        const lieferterminDate = new Date(Liefertermin);
        const erfassungsdatumDate = new Date(Datum_Erfassung);
        const bc = colorMapping[Status] || '00b961';
        const strKunde = `<span style="padding-left:2px;font-size:14px;">${KundenMatchcode}</span>`;
        const showPosStyle = ShowPos ? '' : 'display:none;';
        const stateImg = ShowPos ? 'up.png' : 'dn.png';
        const strTags = Tags.map(tag => `[${tag.lTagId}:${tag.szName}]`).join('');
        let styleLT = '';
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const diffDays = Math.round((lieferterminDate - today) / (1000 * 60 * 60 * 24));
        if (diffDays <= 0) styleLT = 'color:red;';
        else if (diffDays > 0 && diffDays <= 3) styleLT = 'color:#e55f00;';
        const kennungStr = (AuftragsKennung == 2) ? 'LS' : 'AB';
        const tagIcon = isEvent ? 'inprod.svg' : (iconMapping[Tags[0]?.lTagId] || 'neu.svg');

        let html = `
            <li class="no-drag" id="${AuftragId}" style="min-height:34px;" data-status="${Status}" data-draggable="${isEvent || AuftragId === 'V_99999' ? 'false' : 'true'}">
                <div style="height:5px;background-color:#${bc}"></div>
                <div class="order-container">
                    <div class="table-orderinfo">
                        <div class="table-row-orderinfo">
                            <div class="table-cell-kunde">${strKunde}</div>
                            <div class="table-cell-delivery"></div>
                            <div class="table-cell-delivery">
                                ${isEvent ? '' : `
                                <form id="f_${AuftragId}" name="f_${AuftragId}" method="POST" class="delivery-form">
                                    <input type="hidden" class="form-control" id="AuftragId" name="AuftragId" value="${AuftragId}" />
                                    <input type="hidden" class="form-control" id="Tag" name="Tag" value="${Tags[0]?.lTagId || ''}" />
                                    <input type="image" src="img/UI/${tagIcon}" class="delivery-button confirm" value="" id="delivery_button" name="delivery_button" ${isEvent || AuftragId.startsWith('V_') ? 'disabled' : ''} />
                                </form>
                                `}
                            </div>
                            <div class="table-cell-liefertermin" style="padding-right:5px;${styleLT}">${lieferterminDate.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: '2-digit' })}</div>
                        </div>
                        <div class="table-row-orderinfo" style="position: relative; top:-4px;">
                            <div class="table-cell-AuftragsNr">${isEvent ? 'EVENT ' : kennungStr + ' '}${AuftragsNr} ${erfassungsdatumDate.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: '2-digit' })}</div>
                            <div class="table-cell-delivery"></div>
                            <div class="table-cell-delivery"></div>
                            <div class="table-cell-BestellNr">Bst: ${BestellNr}</div>
                        </div>
                    </div>
                    <div style="width:auto;height:auto;text-align:center;border:0px solid blue;margin-top:-26px;">
                        <img style="position: relative; top:-3px;" class="apply4job" id="${AuftragId}" height="12px" src="img/UI/${stateImg}">
                        <div style="${showPosStyle}">
                            <div class="table-positions" style="margin-top:6px;">
        `;

        for (const pos of Object.values(Positionen || [])) {
            const { PosNr, ArtikelId, ArtikelNr, Artikel_Bezeichnung, Artikel_Menge, Artikel_LagerId } = pos;
            const available = Artikel_Menge + 5; // Mocked available quantity
            const checkIcon = isEvent ? 'check_done.png' : (Status === 2 ? 'check_inproc.png' : 'check_done.png');
            html += `
                <div class="table-row-artikelpos" ArtikelId="${ArtikelId}">
                    <div class="artikel-pos-artikelnr">${ArtikelNr}</div>
                    <div class="artikel-pos-bez">${Artikel_Bezeichnung}</div>
                    <div class="artikel-pos-verfuegbar">(${available})</div>
                    <div class="artikel-pos-menge">${Artikel_Menge.toLocaleString('de-DE', { minimumFractionDigits: 0 })}</div>
                    <div class="artikel-pos-check"><img src="img/UI/${checkIcon}" width="8px" height="8px" style="margin-top:-1px;" /></div>
                </div>
            `;
        }

        html += `
                            </div>
                        </div>
                    </div>
                </div>
            </li>
        `;

        return html;
    }

    // Initialize dragula
    const drake = dragula([
        document.getElementById('1'),
        document.getElementById('2'),
        document.getElementById('3'),
        document.getElementById('4')
    ], {
        accepts: function (el, target, source, sibling) {
            if (!el.id || el.dataset.draggable === 'false') {
                console.log(`Skipping drop for invalid element: id=${el.id}, draggable=${el.dataset.draggable}`);
                return false;
            }
            if (el.id === 'V_99999') {
                console.log('Prevented dragging of V_99999');
                return false;
            }
            const currentStatus = parseInt(el.dataset.status) || 0;
            const targetId = parseInt(target.id);
            console.log(`Attempting to drop order ${el.id} from status ${currentStatus} to column ${targetId}`);
            return true;
        }
    })
    .on('drag', function (el) {
        el.className = el.className.replace('ex-moved', '');
    })
    .on('drop', function (el, target, source) {
        if (!el.id || el.dataset.draggable === 'false') {
            console.log(`Skipping drop handling for invalid element: id=${el.id}`);
            return;
        }
        el.className += ' ex-moved';
        const AuftragId = el.id;
        const Status = parseInt(target.id);
        const AuftragsKennung = Status === 4 ? 2 : 1;
        const Tag = tagMapping[Status];

        const isVirtual = AuftragId.startsWith('V_') || AuftragId.startsWith('E_');
        const targetOrders = isVirtual ? virtualOrders : orders;
        targetOrders[AuftragId] = {
            Status: Status,
            AuftragsKennung: AuftragsKennung,
            Tags: [Tag],
            ShowPos: targetOrders[AuftragId]?.ShowPos ?? 1
        };
        localStorage.setItem(isVirtual ? 'virtual_orders' : 'orders', JSON.stringify(targetOrders));
        console.log(`Updated LocalStorage for ${AuftragId} (${isVirtual ? 'virtual' : 'real'}):`, targetOrders[AuftragId]);

        el.dataset.status = Status;

        const colorBar = el.querySelector('div[style*="background-color"]');
        if (colorBar) {
            colorBar.style.backgroundColor = `#${colorMapping[Status]}`;
            console.log(`Updated color for ${AuftragId} to ${colorMapping[Status]}`);
        }

        const deliveryButton = el.querySelector('.delivery-button');
        if (deliveryButton) {
            const newIcon = iconMapping[Tag.lTagId] || 'neu.svg';
            deliveryButton.src = `img/UI/${newIcon}`;
            deliveryButton.disabled = isVirtual;
            console.log(`Updated icon for ${AuftragId} to ${newIcon}`);
        }

        sortColumn(source);
        sortColumn(target);

        if (typeof APP_MODE !== 'undefined' && APP_MODE === 'online' && !isVirtual) {
            $.ajax({
                type: 'POST',
                url: 'actions.php',
                data: {
                    do: 'setDeliveryStatus',
                    AuftragId: AuftragId,
                    Status: Status,
                    Tag: Tag.lTagId
                },
                success: function(data) {
                    console.log('Backend updated:', data);
                },
                error: function(xhr) {
                    console.error('Backend update failed:', xhr.responseText);
                }
            });
        }
    })
    .on('over', function (el, container) {
        container.className += ' ex-over';
    })
    .on('out', function (el, container) {
        container.className = container.className.replace('ex-over', '');
    });

    // Update ShowPos in LocalStorage
    function sendShowPos(AuftragId, State) {
        if (!AuftragId || AuftragId === '0') {
            console.warn(`Invalid AuftragId for ShowPos: ${AuftragId}`);
            return;
        }
        const isVirtual = AuftragId.startsWith('V_') || AuftragId.startsWith('E_');
        const targetOrders = isVirtual ? virtualOrders : orders;
        targetOrders[AuftragId] = targetOrders[AuftragId] || {
            Status: 1,
            AuftragsKennung: 1,
            Tags: [tagMapping[1]],
            ShowPos: 1
        };
        targetOrders[AuftragId].ShowPos = State;
        localStorage.setItem(isVirtual ? 'virtual_orders' : 'orders', JSON.stringify(targetOrders));
        console.log(`ShowPos updated for ${AuftragId} (${isVirtual ? 'virtual' : 'real'}):`, targetOrders[AuftragId]);
    }

    // Clock and date display
    function showTime() {
        const options = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' };
        const zeroPad = (num, places) => String(num).padStart(places, '0');
        
        const date = new Date();
        const h = date.getHours();
        const m = date.getMinutes();
        const datestr = date.toLocaleDateString('de-DE', options);
        const time = zeroPad(h, 2) + ":" + zeroPad(m, 2);
        
        const clock = document.getElementById("MyClockDisplay");
        const dateDisplay = document.getElementById("MyDateDisplay");
        if (clock) {
            clock.innerText = time;
            clock.textContent = time;
        }
        if (dateDisplay) {
            dateDisplay.innerText = datestr;
            dateDisplay.textContent = datestr;
        }
        
        setTimeout(showTime, 1000);
    }

    // Loading animation for delivery button
    function Loading(f_button) {
        f_button.prop('disabled', true);
        f_button.attr('style', 'width:14px; height:14px;');
        if (f_button.attr('src')) {
            f_button.attr('src', 'img/UI/loading.gif');
        }
        setTimeout(function() {
            f_button.html('<span style="color:#00FF00;">Gespeichert</span>');
            f_button.attr('style', 'background:url(img/UI/delivery_1.svg) no-repeat;');
            if (f_button.attr('src')) {
                f_button.attr('src', 'img/UI/delivery_1.svg');
            }
            const AuftragId = f_button.closest('form').find('input[name="AuftragId"]').val();
            if (!AuftragId || AuftragId === '0') {
                console.error(`Invalid AuftragId in Loading: ${AuftragId}`);
                return;
            }
            const isVirtual = AuftragId.startsWith('V_') || AuftragId.startsWith('E_');
            if (isVirtual) {
                console.log(`Skipping move for virtual order ${AuftragId}`);
                return;
            }
            const orderEl = document.getElementById(AuftragId);
            const shippedColumn = document.getElementById('4');
            if (orderEl && shippedColumn && orderEl !== shippedColumn && !shippedColumn.contains(orderEl)) {
                shippedColumn.appendChild(orderEl);
                orderEl.dataset.status = '4';
                const colorBar = orderEl.querySelector('div[style*="background-color"]');
                if (colorBar) {
                    colorBar.style.backgroundColor = `#${colorMapping[4]}`;
                }
                const deliveryButton = orderEl.querySelector('.delivery-button');
                if (deliveryButton) {
                    deliveryButton.src = 'img/UI/delivery_1.svg';
                    deliveryButton.disabled = false;
                }
                console.log(`Moved order ${AuftragId} to SHIPPED column`);
                sortColumn(shippedColumn);
            }
        }, 2000);
    }

    // WebSocket connection
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
                    message.data.orders.forEach(orderMsg => {
                        if (orderMsg.type === 'order_created') {
                            handleOrderCreated(orderMsg.data);
                        }
                    });

                    // Process initial events
                    message.data.events.forEach(eventMsg => {
                        if (eventMsg.type === 'event_created') {
                            handleEventCreated(eventMsg.data);
                        }
                    });

                    // Apply LocalStorage states after rendering
                    applyOrders(orders, false);
                    applyOrders(virtualOrders, true);
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
        const column = document.getElementById(order.Status.toString());
        if (!column) {
            console.warn(`Column not found for status ${order.Status}`);
            return;
        }

        // Check if order already exists
        if (document.getElementById(order.AuftragId)) {
            console.log(`Order ${order.AuftragId} already exists, updating instead`);
            handleOrderUpdated(order);
            return;
        }

        const html = renderItem(order, false);
        column.insertAdjacentHTML('beforeend', html);
        console.log(`Added order ${order.AuftragId} to column ${order.Status}`);
        sortColumn(column);
    }

    function handleOrderUpdated(order) {
        const orderEl = document.getElementById(order.AuftragId);
        if (!orderEl) {
            console.log(`Order ${order.AuftragId} not found, creating instead`);
            handleOrderCreated(order);
            return;
        }

        const currentColumnId = orderEl.parentNode?.id;
        const targetColumnId = order.Status.toString();
        if (currentColumnId !== targetColumnId) {
            const targetColumn = document.getElementById(targetColumnId);
            if (targetColumn) {
                targetColumn.appendChild(orderEl);
                console.log(`Moved order ${order.AuftragId} to column ${targetColumnId}`);
            }
        }

        // Update visuals
        orderEl.dataset.status = order.Status;
        const colorBar = orderEl.querySelector('div[style*="background-color"]');
        if (colorBar) {
            colorBar.style.backgroundColor = `#${colorMapping[order.Status]}`;
        }
        const deliveryButton = orderEl.querySelector('.delivery-button');
        if (deliveryButton) {
            const newIcon = iconMapping[order.Tags[0]?.lTagId] || 'neu.svg';
            deliveryButton.src = `img/UI/${newIcon}`;
        }

        sortColumn(document.getElementById(targetColumnId));
        console.log(`Updated order ${order.AuftragId}`);
    }

    function handleOrderDeleted(data) {
        const orderEl = document.getElementById(data.AuftragId);
        if (orderEl) {
            const column = orderEl.parentNode;
            orderEl.remove();
            console.log(`Removed order ${data.AuftragId}`);
            sortColumn(column);
        }
    }

    function handleEventCreated(event) {
        const column = document.getElementById('1'); // Events go in NEU column
        if (!column) {
            console.warn(`Column not found for events (status 1)`);
            return;
        }

        if (document.getElementById(event.AuftragId)) {
            console.log(`Event ${event.AuftragId} already exists, updating instead`);
            handleEventUpdated(event);
            return;
        }

        const html = renderItem(event, true);
        column.insertAdjacentHTML('beforeend', html);
        console.log(`Added event ${event.AuftragId} to column 1`);
        sortColumn(column);
    }

    function handleEventUpdated(event) {
        const eventEl = document.getElementById(event.AuftragId);
        if (!eventEl) {
            console.log(`Event ${event.AuftragId} not found, creating instead`);
            handleEventCreated(event);
            return;
        }

        // Events stay in column 1, just update visuals
        eventEl.dataset.status = event.Status;
        const colorBar = eventEl.querySelector('div[style*="background-color"]');
        if (colorBar) {
            colorBar.style.backgroundColor = `#${colorMapping[event.Status]}`;
        }
        console.log(`Updated event ${event.AuftragId}`);
    }

    function handleEventDeleted(data) {
        const eventEl = document.getElementById(data.AuftragId);
        if (eventEl) {
            const column = eventEl.parentNode;
            eventEl.remove();
            console.log(`Removed event ${data.AuftragId}`);
            sortColumn(column);
        }
    }

    // Apply LocalStorage to DOM
    function applyOrders(orders, isVirtual = false) {
        const seenElements = new Set();
        document.querySelectorAll('.drag-inner-list li').forEach(el => {
            const auftragsNr = el.id;
            if (!auftragsNr) return;
            if (seenElements.has(auftragsNr)) {
                console.log(`Removing duplicate element for AuftragsNr: ${auftragsNr} (${isVirtual ? 'virtual' : 'real'})`);
                el.remove();
            } else {
                seenElements.add(auftragsNr);
            }
        });

        Object.keys(orders).forEach(auftragsNr => {
            const order = orders[auftragsNr];
            let orderEl = document.getElementById(auftragsNr);
            if (!orderEl) {
                console.warn(`Order element not found for AuftragsNr: ${auftragsNr} (${isVirtual ? 'virtual' : 'real'})`);
                return;
            }
            if (auftragsNr === 'V_99999') {
                const productionColumn = document.getElementById('2');
                if (orderEl.parentNode !== productionColumn) {
                    productionColumn.appendChild(orderEl);
                    order.Status = 2;
                    order.AuftragsKennung = 1;
                    order.Tags = [tagMapping[2]];
                    virtualOrders[auftragsNr] = order;
                    localStorage.setItem('virtual_orders', JSON.stringify(virtualOrders));
                    console.log('Forced V_99999 back to Production column');
                }
            } else {
                const currentColumnId = orderEl.parentNode?.id;
                const targetColumnId = order.Status.toString();
                if (order.Status && currentColumnId !== targetColumnId) {
                    const targetColumn = document.getElementById(targetColumnId);
                    if (targetColumn && orderEl !== targetColumn && !targetColumn.contains(orderEl)) {
                        targetColumn.appendChild(orderEl);
                        orderEl.dataset.status = order.Status;
                        console.log(`Moved order ${auftragsNr} (${isVirtual ? 'virtual' : 'real'}) to column ${order.Status}`);
                    }
                }
            }
            const toggleImg = orderEl.querySelector('.apply4job');
            const posDiv = orderEl.querySelector('.table-positions')?.parentNode;
            if (toggleImg && posDiv) {
                toggleImg.src = order.ShowPos ? 'img/UI/up.png' : 'img/UI/dn.png';
                posDiv.style.display = order.ShowPos ? '' : 'none';
                console.log(`Set ShowPos for ${auftragsNr} (${isVirtual ? 'virtual' : 'real'}): ${order.ShowPos}`);
            }
            const colorBar = orderEl.querySelector('div[style*="background-color"]');
            if (colorBar) {
                colorBar.style.backgroundColor = `#${colorMapping[order.Status]}`;
            }
            const deliveryButton = orderEl.querySelector('.delivery-button');
            if (deliveryButton) {
                const newIcon = iconMapping[order.Tags[0]?.lTagId] || 'neu.svg';
                deliveryButton.src = `img/UI/${newIcon}`;
                deliveryButton.disabled = isVirtual;
            }
        });

        document.querySelectorAll('.drag-inner-list').forEach(column => {
            sortColumn(column);
        });
    }

    // Document ready
    $(document).ready(function() {
        // Connect to WebSocket
        connectWebSocket();

        // Delivery button form submission
        $('body').on('submit', 'form.delivery-form', function(event) {
            event.preventDefault();
            
            const f_button = $(this).find('input[name="delivery_button"]');
            const v_AuftragId = $(this).find('input[name="AuftragId"]').val();
            const v_Tag = $(this).find('input[name="Tag"]').val();

            if (!v_AuftragId || v_AuftragId === '0') {
                console.error(`Invalid form data: AuftragId=${v_AuftragId}, Tag=${v_Tag}`);
                f_button.html('<span style="color:red;">Invalid</span>');
                f_button.attr('style', '');
                return;
            }

            const isVirtual = v_AuftragId.startsWith('V_') || v_AuftragId.startsWith('E_');
            if (isVirtual) {
                console.log(`Skipping delivery update for virtual order ${v_AuftragId}`);
                f_button.html('<span style="color:red;">Virtual</span>');
                f_button.attr('style', '');
                return;
            }

            console.log(`Delivery button clicked for AuftragId: ${v_AuftragId}, Tag: ${v_Tag}`);

            orders[v_AuftragId] = {
                Status: 4,
                AuftragsKennung: 2,
                Tags: [tagMapping[4]],
                ShowPos: orders[v_AuftragId]?.ShowPos ?? 1
            };
            localStorage.setItem('orders', JSON.stringify(orders));
            console.log(`LocalStorage updated for delivery ${v_AuftragId}:`, orders[v_AuftragId]);

            $(".form-group").removeClass("has-error");
            $(".help-block").remove();

            const formData = {
                'do': 'setDeliveryStatus',
                AuftragId: v_AuftragId,
                Tag: v_Tag
            };

            $.ajax({
                type: "POST",
                url: "actions.php",
                data: formData,
                cache: false,
                dataType: "JSON",
                encode: true
            })
            .done(function(data) {
                if (!data.success) {
                    if (data.errors.AuftragId) {
                        $("#AuftragId-group").addClass("has-error");
                        f_button.html(data.errors.AuftragId);
                        f_button.attr('style', '');
                    }
                    if (data.errors.Tag) {
                        $("#Tag-group").addClass("has-error");
                        f_button.html(data.errors.Tag);
                        f_button.attr('style', '');
                    }
                    console.log('Errors:', data.errors);
                } else {
                    Loading(f_button);
                    $("#dialog").dialog("close");
                }
            })
            .fail(function(data) {
                console.error('AJAX failed:', data.responseText);
                f_button.html('<span style="color:red;">!conn</span>');
                f_button.attr('style', '');
            });
        });

        // ShowPos toggle
        $('body').on('click', '.apply4job', function() {
            const $this = $(this);
            const AuftragId = $this.attr('id');
            if (!AuftragId || AuftragId === '0') {
                console.warn(`Invalid AuftragId for apply4job: ${AuftragId}`);
                return;
            }
            $this.next().slideToggle();
            const State = $this.attr('src') === "img/UI/up.png" ? 0 : 1;
            $this.attr('src', State === 0 ? "img/UI/dn.png" : "img/UI/up.png");
            sendShowPos(AuftragId, State);
        });

        showTime();
    });
});