import OrderItem from './OrderItem';
import { useDroppable } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';

import type { Order } from '../types';

interface OrderColumnProps {
  status: number;
  orders: Order[];
}

function OrderColumn({ status, orders }: OrderColumnProps) {
  const { setNodeRef } = useDroppable({
    id: `column-${status}`,
  });

  return (
    <div ref={setNodeRef} className="order-container">
      <SortableContext
        id={`column-${status}`}
        items={orders.map(order => order.AuftragId)}
        strategy={verticalListSortingStrategy}
        disabled={false}
      >
        {orders.map((order) => (
          <OrderItem key={order.AuftragId} order={order} status={status} />
        ))}
      </SortableContext>
    </div>
  );
}

export default OrderColumn;