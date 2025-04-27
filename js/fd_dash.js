$(function() {
    // Initialize and flatten LocalStorage
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

    // Remove invalid keys
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

    // Tag mapping for status updates
    const tagMapping = {
        1: { lTagId: 4, szName: 'Neu' },
        2: { lTagId: 2, szName: 'Produktion' },
        3: { lTagId: 5, szName: 'Versandbereit' },
        4: { lTagId: 1, szName: 'Versendet' },
        5: { lTagId: 6, szName: 'Fakturieren' }
    };

    // Color mapping for status (same as lx_orders.php)
    const colorMapping = {
        1: 'fb7d44', // Neu (Orange)
        2: '2a92bf', // Produktion (Blue)
        3: 'f4ce46', // Versandbereit (Yellow)
        4: '00b961', // Versendet (Green)
        5: '00b961'  // Fakturieren (Green)
    };

    // Icon mapping for status (same as lx_orders.php)
    const iconMapping = {
        4: 'neu.svg',         // Neu
        2: 'inprod.svg',      // Produktion
        5: 'vorb.svg',        // Versandbereit
        1: 'delivery_0.svg',  // Versendet
        6: 'fakturieren.svg'  // Fakturieren
    };

    // Function to parse delivery date from DOM (format: DD.MM.YY)
    function parseDeliveryDate(dateStr) {
        const parts = dateStr.split('.');
        if (parts.length !== 3) return null;
        const day = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1; // Months are 0-based in JS
        const year = 2000 + parseInt(parts[2], 10); // Assuming 20XX
        return new Date(year, month, day);
    }

    // Function to sort orders within a column by delivery date (ascending)
    function sortColumn(column) {
        const columnId = column.id;
        const items = Array.from(column.children)
            .filter(el => el.id && el.classList.contains('no-drag'));

        // Separate V_99999 if in Production column (id="2")
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

        // Sort other items by delivery date (ascending)
        otherItems.sort((a, b) => {
            const dateA = parseDeliveryDate(a.querySelector('.table-cell-liefertermin')?.textContent.trim());
            const dateB = parseDeliveryDate(b.querySelector('.table-cell-liefertermin')?.textContent.trim());
            if (!dateA || !dateB) return 0;
            return dateA - dateB; // Ascending
        });

        // Rebuild column
        while (column.firstChild) {
            column.removeChild(column.firstChild);
        }
        otherItems.forEach(item => column.appendChild(item));
        if (v99999) {
            column.appendChild(v99999); // Always append V_99999 at the bottom
            console.log('Pinned V_99999 to bottom of Production column');
        }

        console.log(`Sorted column ${columnId} by delivery date (ascending)`);
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
            // Prevent V_99999 from being dragged
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

        // Update LocalStorage
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

        // Update data-status attribute
        el.dataset.status = Status;

        // Update visual elements
        const colorBar = el.querySelector('div[style*="background-color"]');
        if (colorBar) {
            colorBar.style.backgroundColor = `#${colorMapping[Status]}`;
            console.log(`Updated color for ${AuftragId} to ${colorMapping[Status]}`);
        } else {
            console.warn(`Color bar not found for ${AuftragId}`);
        }

        const deliveryButton = el.querySelector('.delivery-button');
        if (deliveryButton) {
            const newIcon = iconMapping[Tag.lTagId] || 'neu.svg';
            deliveryButton.src = `img/UI/${newIcon}`;
            deliveryButton.disabled = isVirtual;
            console.log(`Updated icon for ${AuftragId} to ${newIcon}`);
        } else {
            console.warn(`Delivery button not found for ${AuftragId}`);
        }

        // Sort both source and target columns after drop
        sortColumn(source);
        sortColumn(target);

        // Sync with backend in online mode (only for real orders)
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
        } else {
            console.warn('MyClockDisplay element not found');
        }
        if (dateDisplay) {
            dateDisplay.innerText = datestr;
            dateDisplay.textContent = datestr;
        } else {
            console.warn('MyDateDisplay element not found');
        }
        
        setTimeout(showTime, 1000);
    }

    // Loading animation for delivery button
    function Loading(f_button) {
        f_button.prop('disabled', true);
        f_button.attr('style', 'width:14px; height:14px;');
        if (f_button.attr('src')) {
            f_button.attr('src', 'img/UI/loading.gif');
        } else {
            f_button.html('<img src="img/UI/loading.gif" height="14px" />');
        }
        setTimeout(function() {
            f_button.html('<span style="color:#00FF00;">Gespeichert</span>');
            f_button.attr('style', 'background:url(img/UI/delivery_1.svg) no-repeat;');
            if (f_button.attr('src')) {
                f_button.attr('src', 'img/UI/delivery_1.svg');
            } else {
                f_button.html(' ');
            }
            // Move order to SHIPPED column
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
                // Update visuals after delivery
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
                // Sort the shipped column
                sortColumn(shippedColumn);
            } else {
                console.error(`Failed to move order ${AuftragId}: orderEl=${!!orderEl}, shippedColumn=${!!shippedColumn}, alreadyContains=${shippedColumn?.contains(orderEl)}`);
            }
        }, 2000);
    }

    // Document ready
    $(document).ready(function() {
        // Delivery button form submission
        $('input[name="delivery_button"]').closest("form").submit(function(event) {
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

            // Update LocalStorage
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

            // Sync with backend
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
        $(".apply4job").on("click", function() {
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

        // Apply LocalStorage to DOM and remove duplicates
        function applyOrders(orders, isVirtual = false) {
            // First, find and remove duplicate elements
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

            // Now apply LocalStorage states
            Object.keys(orders).forEach(auftragsNr => {
                const order = orders[auftragsNr];
                let orderEl = document.getElementById(auftragsNr);
                if (!orderEl) {
                    console.warn(`Order element not found for AuftragsNr: ${auftragsNr} (${isVirtual ? 'virtual' : 'real'})`);
                    return;
                }
                // Prevent moving V_99999 from Production column
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
                            try {
                                targetColumn.appendChild(orderEl);
                                orderEl.dataset.status = order.Status;
                                console.log(`Moved order ${auftragsNr} (${isVirtual ? 'virtual' : 'real'}) to column ${order.Status}`);
                            } catch (e) {
                                console.error(`Failed to move order ${auftragsNr} (${isVirtual ? 'virtual' : 'real'}) to column ${order.Status}: ${e.message}`);
                            }
                        } else if (!targetColumn) {
                            console.warn(`Target column ${order.Status} not found for ${auftragsNr} (${isVirtual ? 'virtual' : 'real'})`);
                        } else {
                            console.log(`Order ${auftragsNr} (${isVirtual ? 'virtual' : 'real'}) already in column ${order.Status}`);
                        }
                    } else {
                        console.log(`Order ${auftragsNr} (${isVirtual ? 'virtual' : 'real'}) already in correct column ${currentColumnId}`);
                    }
                }
                const toggleImg = orderEl.querySelector('.apply4job');
                const posDiv = orderEl.querySelector('.table-positions')?.parentNode;
                if (toggleImg && posDiv) {
                    toggleImg.src = order.ShowPos ? 'img/UI/up.png' : 'img/UI/dn.png';
                    posDiv.style.display = order.ShowPos ? '' : 'none';
                    console.log(`Set ShowPos for ${auftragsNr} (${isVirtual ? 'virtual' : 'real'}): ${order.ShowPos}`);
                } else {
                    console.warn(`Toggle image or positions div not found for ${auftragsNr} (${isVirtual ? 'virtual' : 'real'})`);
                }
                // Ensure visuals match status
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

            // Sort all columns after applying orders
            document.querySelectorAll('.drag-inner-list').forEach(column => {
                sortColumn(column);
            });
        }

        applyOrders(orders, false);
        applyOrders(virtualOrders, true);

        // Log initial LocalStorage for debugging
        console.log('Initial LocalStorage orders:', orders);
        console.log('Initial LocalStorage virtual_orders:', virtualOrders);

        showTime();
    });
});