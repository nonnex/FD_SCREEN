function initUI() {
    // Initialize dragula
    const drake = dragula([
        document.getElementById('column-1'),
        document.getElementById('column-2'),
        document.getElementById('column-3'),
        document.getElementById('column-4')
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
            const targetId = parseInt(target.id.replace('column-', ''));
            console.log(`Attempting to drop order ${el.id} from status ${currentStatus} to column ${targetId}`);
            return true;
        }
    })
    .on('drag', function (el) {
        el.className = el.className.replace('ex-moved', '');
    })
    .on('drop', async function (el, target, source) {
        if (!el.id || el.dataset.draggable === 'false') {
            console.log(`Skipping drop handling for invalid element: id=${el.id}`);
            return;
        }
        el.className += ' ex-moved';
        const AuftragId = el.id;
        const Status = parseInt(target.id.replace('column-', ''));
        const AuftragsKennung = Status === 4 ? 2 : 1;
        const Tag = utils.tagMapping[Status];

        const isVirtual = AuftragId.startsWith('V_') || AuftragId.startsWith('E_');
        // Load the full order object from LocalStorage
        const { orders, virtualOrders } = await utils.loadOrders();
        const targetOrders = isVirtual ? virtualOrders : orders;
        const order = targetOrders[AuftragId];

        if (!order) {
            console.error(`Order ${AuftragId} not found in LocalStorage`);
            return;
        }

        // Update the relevant fields
        order.Status = Status;
        order.AuftragsKennung = AuftragsKennung;
        order.Tags = [Tag];

        // Save the full order object back to LocalStorage
        window.ordersState = await utils.saveOrder(order, isVirtual);
        console.log(`Updated LocalStorage for ${AuftragId} (${isVirtual ? 'virtual' : 'real'}):`, order);

        el.dataset.status = Status;

        const colorBar = el.querySelector('div[style*="background-color"]');
        if (colorBar) {
            colorBar.style.backgroundColor = `#${utils.colorMapping[Status]}`;
            console.log(`Updated color for ${AuftragId} to ${utils.colorMapping[Status]}`);
        }

        const deliveryButton = el.querySelector('.delivery-button');
        if (deliveryButton) {
            const newIcon = utils.iconMapping[Tag.lTagId] || 'neu.svg';
            deliveryButton.src = `img/UI/${newIcon}`;
            deliveryButton.disabled = isVirtual;
            console.log(`Updated icon for ${AuftragId} to ${newIcon}`);
        }

        utils.sortColumn(source);
        utils.sortColumn(target);

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
    async function sendShowPos(AuftragId, State) {
        if (!AuftragId || AuftragId === '0') {
            console.warn(`Invalid AuftragId for ShowPos: ${AuftragId}`);
            return;
        }
        const isVirtual = AuftragId.startsWith('V_') || AuftragId.startsWith('E_');
        const { orders, virtualOrders } = await utils.loadOrders();
        const targetOrders = isVirtual ? virtualOrders : orders;
        const order = targetOrders[AuftragId] || {
            AuftragId: AuftragId,
            Status: 1,
            AuftragsKennung: 1,
            Tags: [utils.tagMapping[1]],
            ShowPos: 1
        };
        order.ShowPos = State;
        window.ordersState = await utils.saveOrder(order, isVirtual);
        console.log(`ShowPos updated for ${AuftragId} (${isVirtual ? 'virtual' : 'real'}):`, order);
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
    async function Loading(f_button) {
        f_button.prop('disabled', true);
        f_button.attr('style', 'width:14px; height:14px;');
        if (f_button.attr('src')) {
            f_button.attr('src', 'img/UI/loading.gif');
        }
        setTimeout(async function() {
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
            const shippedColumn = document.getElementById('column-4');
            if (orderEl && shippedColumn && orderEl.parentNode !== shippedColumn) {
                shippedColumn.appendChild(orderEl);
                orderEl.dataset.status = '4';
                const colorBar = orderEl.querySelector('div[style*="background-color"]');
                if (colorBar) {
                    colorBar.style.backgroundColor = `#${utils.colorMapping[4]}`;
                }
                const deliveryButton = orderEl.querySelector('.delivery-button');
                if (deliveryButton) {
                    deliveryButton.src = 'img/UI/delivery_1.svg';
                    deliveryButton.disabled = false;
                }
                console.log(`Moved order ${AuftragId} to SHIPPED column`);
                utils.sortColumn(shippedColumn);

                // Update LocalStorage
                const { orders } = await utils.loadOrders();
                const order = orders[AuftragId];
                if (order) {
                    order.Status = 4;
                    order.AuftragsKennung = 2;
                    order.Tags = [utils.tagMapping[4]];
                    window.ordersState = await utils.saveOrder(order, false);
                    console.log(`Updated LocalStorage after delivery for ${AuftragId}:`, order);
                }
            }
        }, 2000);
    }

    // Event handlers
    $('body').on('submit', 'form.delivery-form', async function(event) {
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

        const { orders } = await utils.loadOrders();
        const order = orders[v_AuftragId];
        if (order) {
            order.Status = 4;
            order.AuftragsKennung = 2;
            order.Tags = [utils.tagMapping[4]];
            window.ordersState = await utils.saveOrder(order, false);
            console.log(`LocalStorage updated for delivery ${v_AuftragId}:`, order);
        }

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

    $('body').on('click', '.apply4job', async function() {
        const $this = $(this);
        const AuftragId = $this.attr('id');
        if (!AuftragId || AuftragId === '0') {
            console.warn(`Invalid AuftragId for apply4job: ${AuftragId}`);
            return;
        }
        $this.next().slideToggle();
        const State = $this.attr('src') === "img/UI/up.png" ? 0 : 1;
        $this.attr('src', State === 0 ? "img/UI/dn.png" : "img/UI/up.png");
        await sendShowPos(AuftragId, State);
    });

    showTime();
}

window.ui = {
    initUI
};