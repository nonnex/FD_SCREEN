import { useEffect, useState, useCallback } from 'react';
import '../App.css';
import OrderColumn from '../components/OrderColumn';
import OrderItem from '../components/OrderItem';
import { useAppContext } from '../AppContext';
import { DndContext, closestCorners, DragOverlay, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import type { DragEndEvent, DragStartEvent, DragOverEvent } from '@dnd-kit/core';
import { sortableKeyboardCoordinates } from '@dnd-kit/sortable';

function Dashboard() {
  const [currentTime, setCurrentTime] = useState<string>('');
  const [currentDate, setCurrentDate] = useState<string>('');
  const [activeId, setActiveId] = useState<string | null>(null);
  const { orders, virtualOrders, events, isConnected, updateOrderStatus, reorderOrders } = useAppContext();

  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates
    })
  );

  useEffect(() => {
    // Uhrzeit und Datum aktualisieren
    const updateTime = () => {
      const now = new Date();
      setCurrentTime(now.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
      setCurrentDate(now.toLocaleDateString('de-DE', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }));
    };
    updateTime();
    const interval = setInterval(updateTime, 1000);

    return () => clearInterval(interval);
  }, []);

  // Drag-and-Drop-Handler
  const handleDragStart = useCallback((event: DragStartEvent) => {
    const { active } = event;
    setActiveId(active.id as string);
  }, [setActiveId]);

  const findContainer = useCallback((id: string): number | undefined => {
    const order = orders.find(order => order.AuftragId === id);
    return order ? order.Status : undefined;
  }, [orders]);

  const handleDragOver = useCallback((event: DragOverEvent) => {
    const { active, over } = event;
    if (!over) return;

    const sourceId = active.id as string;
    const destinationId = over.id as string;

    const activeContainer = findContainer(sourceId);
    let overContainer: number | undefined;

    if (destinationId.startsWith('column-')) {
      const match = destinationId.match(/column-(\d+)/);
      overContainer = match ? parseInt(match[1]) : undefined;
    } else {
      overContainer = findContainer(destinationId);
    }

    if (!activeContainer || !overContainer || activeContainer === overContainer) {
      return;
    }

    updateOrderStatus(sourceId, overContainer);
    console.log(`Bestellung ${sourceId} wurde von Status ${activeContainer} zu ${overContainer} verschoben.`);
  }, [updateOrderStatus, findContainer]);

  const handleDragEnd = useCallback((event: DragEndEvent) => {
    const { active, over } = event;
    if (!over) {
      setActiveId(null);
      return;
    }

    const sourceId = active.id as string;
    const destinationId = over.id as string;

    const activeContainer = findContainer(sourceId);
    let overContainer: number | undefined;

    if (destinationId.startsWith('column-')) {
      const match = destinationId.match(/column-(\d+)/);
      overContainer = match ? parseInt(match[1]) : undefined;
    } else {
      overContainer = findContainer(destinationId);
    }

    if (!activeContainer || !overContainer || activeContainer !== overContainer) {
      setActiveId(null);
      return;
    }

    const filteredOrders = orders.filter(order => order.Status === activeContainer);
    const activeIndex = filteredOrders.findIndex(order => order.AuftragId === sourceId);
    const overIndex = destinationId.startsWith('column-') ? -1 : filteredOrders.findIndex(order => order.AuftragId === destinationId);

    if (activeIndex !== overIndex && overIndex !== -1) {
      reorderOrders(sourceId, overIndex, activeContainer);
      console.log(`Bestellung ${sourceId} wurde innerhalb von Status ${activeContainer} neu angeordnet.`);
    }

    setActiveId(null);
  }, [orders, reorderOrders, setActiveId, findContainer]);

  return (
    <div className="drag-container">
      <section className="section" style={{ margin: 0, padding: 0 }}>
        <div className="drag-column-header" style={{ margin: 0, padding: 0 }}>
          <div id="MyDateDisplay" className="clock" style={{ width: '35%', textAlign: 'left', paddingLeft: 5, border: '0px solid green' }}>{currentDate}</div>
          <a href="/lager.php"><div className="fd-button">Lagerverwaltung</div></a>
          <a href="/calendar/index.php"><div className="fd-button">Kalender</div></a>
          <div id="MyClockDisplay" className="clock" style={{ width: '30%', textAlign: 'right', paddingRight: 5, border: '0px solid green' }}>{currentTime}</div>
        </div>
        <div style={{ margin: 5, padding: 5, color: isConnected ? 'green' : 'red' }}>
          WebSocket: {isConnected ? 'Verbunden' : 'Nicht verbunden'}
        </div>
      </section>
      <DndContext
        sensors={sensors}
        collisionDetection={closestCorners}
        onDragStart={handleDragStart}
        onDragOver={handleDragOver}
        onDragEnd={handleDragEnd}
      >
        <div className="column-headers">
          <div className="header-on-hold"><h2>NEU</h2></div>
          <div className="header-in-progress"><h2>PRODUKTION</h2></div>
          <div className="header-needs-review"><h2>VERSANDVORBEREITUNG</h2></div>
          <div className="header-approved"><h2>AUSLIEFERUNG</h2></div>
        </div>
        <ul className="drag-list">
          <li key="column-1" className="drag-column drag-column-on-hold">
            <div className="order-container-col">
              <ul className="drag-inner-list" id="column-1">
                <OrderColumn status={1} orders={orders.filter(order => order.Status === 1)} />
              </ul>
            </div>
          </li>
          <li key="column-2" className="drag-column drag-column-in-progress">
            <div className="order-container-col">
              <ul className="drag-inner-list" id="column-2">
                <OrderColumn status={2} orders={orders.filter(order => order.Status === 2)} />
              </ul>
            </div>
          </li>
          <li key="column-3" className="drag-column drag-column-needs-review">
            <div className="order-container-col">
              <ul className="drag-inner-list" id="column-3">
                <OrderColumn status={3} orders={orders.filter(order => order.Status === 3)} />
              </ul>
            </div>
          </li>
          <li key="column-4" className="drag-column drag-column-approved">
            <div className="order-container-col">
              <ul className="drag-inner-list" id="column-4">
                <OrderColumn status={4} orders={orders.filter(order => order.Status === 4)} />
              </ul>
            </div>
          </li>
        </ul>
        <div className="additional-data-section">
          <h3>Virtuelle Aufträge</h3>
          <ul className="virtual-orders-list">
            {virtualOrders.map(vOrder => (
              <li key={vOrder.AuftragId} className="virtual-order-item">
                <div className="table table-orderinfo">
                  <div className="table-row table-row-orderinfo">
                    <div className="table-cell-kunde">{vOrder.Beschreibung}</div>
                    <div className="table-cell-liefertermin">{vOrder.Typ}</div>
                  </div>
                  <div className="table-row">
                    <div className="table-cell table-cell-AuftragsNr">Auftrag: {vOrder.AuftragsNr}</div>
                  </div>
                </div>
              </li>
            ))}
          </ul>
          <h3>Events</h3>
          <ul className="events-list">
            {events.map(event => (
              <li key={event.AuftragId} className="event-item">
                <div className="table table-orderinfo">
                  <div className="table-row table-row-orderinfo">
                    <div className="table-cell-kunde">{event.Titel}</div>
                    <div className="table-cell-liefertermin">{event.Datum}</div>
                  </div>
                  <div className="table-row">
                    <div className="table-cell table-cell-AuftragsNr">Beschreibung: {event.Beschreibung}</div>
                    <div className="table-cell table-cell-BestellNr">Kategorie: {event.Kategorie || 'N/A'}</div>
                  </div>
                </div>
              </li>
            ))}
          </ul>
        </div>
        <DragOverlay>
          {activeId ? (
            (() => {
              const activeOrder = orders.find(order => order.AuftragId === activeId);
              return activeOrder ? <OrderItem order={activeOrder} status={activeOrder.Status} /> : null;
            })()
          ) : null}
        </DragOverlay>
      </DndContext>
    </div>
  );
}

export default Dashboard;