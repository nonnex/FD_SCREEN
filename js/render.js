function renderItem(item, isVirtual = false) {
    const { AuftragId, AuftragsNr, AuftragsKennung, Datum_Erfassung, BestellNr, Liefertermin, KundenNr, KundenMatchcode, Status, ShowPos, Tags, Positionen } = item;

    const lieferterminDate = new Date(Liefertermin);
    const erfassungsdatumDate = new Date(Datum_Erfassung);
    const bc = utils.colorMapping[Status] || '00b961';
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
    const tagIcon = isVirtual ? 'inprod.svg' : (utils.iconMapping[Tags[0]?.lTagId] || 'neu.svg');

    let html = `
        <li class="no-drag" id="${AuftragId}" style="min-height:34px;" data-status="${Status}" data-draggable="${isVirtual || AuftragId === 'V_99999' ? 'false' : 'true'}">
            <div style="height:5px;background-color:#${bc}"></div>
            <div class="order-container">
                <div class="table-orderinfo">
                    <div class="table-row-orderinfo">
                        <div class="table-cell-kunde">${strKunde}</div>
                        <div class="table-cell-delivery"></div>
                        <div class="table-cell-delivery">
                            ${isVirtual ? '' : `
                            <form id="f_${AuftragId}" name="f_${AuftragId}" method="POST" class="delivery-form">
                                <input type="hidden" class="form-control" id="AuftragId" name="AuftragId" value="${AuftragId}" />
                                <input type="hidden" class="form-control" id="Tag" name="Tag" value="${Tags[0]?.lTagId || ''}" />
                                <input type="image" src="img/UI/${tagIcon}" class="delivery-button confirm" value="" id="delivery_button" name="delivery_button" ${isVirtual || AuftragId.startsWith('V_') ? 'disabled' : ''} />
                            </form>
                            `}
                        </div>
                        <div class="table-cell-liefertermin" style="padding-right:5px;${styleLT}">${lieferterminDate.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: '2-digit' })}</div>
                    </div>
                    <div class="table-row-orderinfo" style="position: relative; top:-4px;">
                        <div class="table-cell-AuftragsNr">${isVirtual ? 'EVENT ' : kennungStr + ' '}${AuftragsNr} ${erfassungsdatumDate.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: '2-digit' })}</div>
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
        const checkIcon = isVirtual ? 'check_done.png' : (Status === 2 ? 'check_inproc.png' : 'check_done.png');
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
            const productionColumn = document.getElementById('column-2');
            if (orderEl.parentNode !== productionColumn) {
                productionColumn.appendChild(orderEl);
                order.Status = 2;
                order.AuftragsKennung = 1;
                order.Tags = [utils.tagMapping[2]];
                window.ordersState.virtualOrders[auftragsNr] = order;
                localStorage.setItem('virtual_orders', JSON.stringify(window.ordersState.virtualOrders));
                console.log('Forced V_99999 back to Production column');
            }
        } else {
            const currentColumnId = orderEl.parentNode?.id;
            const targetColumnId = isVirtual ? 'column-1' : `column-${order.Status}`;
            if (order.Status && currentColumnId !== targetColumnId) {
                const targetColumn = document.getElementById(targetColumnId);
                if (targetColumn && orderEl.parentNode !== targetColumn) {
                    targetColumn.appendChild(orderEl);
                    orderEl.dataset.status = order.Status;
                    console.log(`Moved order ${auftragsNr} (${isVirtual ? 'virtual' : 'real'}) to column ${order.Status}`);
                    utils.sortColumn(targetColumn);
                    if (currentColumnId) {
                        utils.sortColumn(document.getElementById(currentColumnId));
                    }
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
            colorBar.style.backgroundColor = `#${utils.colorMapping[order.Status]}`;
        }
        const deliveryButton = orderEl.querySelector('.delivery-button');
        if (deliveryButton) {
            const newIcon = utils.iconMapping[order.Tags[0]?.lTagId] || 'neu.svg';
            deliveryButton.src = `img/UI/${newIcon}`;
            deliveryButton.disabled = isVirtual;
        }
    });

    document.querySelectorAll('.drag-inner-list').forEach(column => {
        utils.sortColumn(column);
    });
}

window.render = {
    renderItem,
    applyOrders
};