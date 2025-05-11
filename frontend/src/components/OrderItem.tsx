import React, { memo } from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

import type { Order } from '../types';

interface OrderItemProps {
  order: Order;
  status: number;
}

function OrderItem({ order, status }: OrderItemProps) {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition
  } = useSortable({ id: order.AuftragId });

  const style: React.CSSProperties = {
    transform: CSS.Transform.toString(transform),
    transition,
    zIndex: transform ? 9999 : 'auto',
    position: 'relative', // Keep position relative to avoid layout shifts
  };

  return (
    <li
      className="drag-item"
      id={order.AuftragId}
      data-status={status}
      ref={setNodeRef}
      style={style}
      {...listeners}
      {...attributes}
    >
      <div className="table table-orderinfo">
        <div className="table-row table-row-orderinfo">
          <div className="table-cell-kunde">{order.KundenMatchcode}</div>
          <div className="table-cell-delivery">
            <form className="delivery-form" action="actions.php" method="post">
              <input type="hidden" name="AuftragId" value={order.AuftragId} />
              <input type="hidden" name="action" value="delivery" />
              <input type="image" className="delivery-button" src={`/UI/delivery_${status === 3 ? '1' : '0'}.svg`} alt="delivery" />
            </form>
          </div>
          <div className="table-cell-liefertermin">{order.Liefertermin}</div>
        </div>
        <div className="table-row">
          <div className="table-cell table-cell-AuftragsNr">Auftrag: {order.AuftragsNr}</div>
          <div className="table-cell table-cell-BestellNr">BestellNr: {order.BestellNr || 'N/A'}</div>
        </div>
      </div>
    </li>
  );
}

export default memo(OrderItem);