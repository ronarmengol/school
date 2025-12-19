<!-- Event Details Modal -->
<div id="eventModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 2000; backdrop-filter: blur(4px); animation: fadeIn 0.3s ease;">
    <div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;">
        <div id="modalContent" style="background: white; border-radius: 16px; max-width: 600px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); animation: slideUp 0.3s ease;">
            <div style="padding: 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 16px 16px 0 0; color: white;">
                <div>
                    <h3 id="modalDate" style="margin: 0; font-size: 1.5rem; font-weight: 600;"></h3>
                    <p id="modalDayName" style="margin: 4px 0 0 0; opacity: 0.9; font-size: 0.875rem;"></p>
                </div>
                <button onclick="closeEventModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.2s;">×</button>
            </div>
            <div id="modalBody" style="padding: 24px;"></div>
        </div>
    </div>
</div>

<style>
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.event-modal-item { padding: 16px; border-radius: 12px; margin-bottom: 12px; background: #f8fafc; transition: all 0.2s; border: 1px solid #e2e8f0; }
.event-modal-item:hover { background: #f1f5f9; transform: translateX(4px); }
.event-modal-item.holiday { background: linear-gradient(90deg, rgba(239, 68, 68, 0.05) 0%, #f8fafc 100%); }
.event-modal-item.event { background: linear-gradient(90deg, rgba(59, 130, 246, 0.05) 0%, #f8fafc 100%); }
.event-modal-item.reminder { background: linear-gradient(90deg, rgba(245, 158, 11, 0.05) 0%, #f8fafc 100%); }
.event-modal-item.exam { background: linear-gradient(90deg, rgba(139, 92, 246, 0.05) 0%, #f8fafc 100%); }
.event-badge { display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.event-badge.holiday { background: #fee2e2; color: #991b1b; }
.event-badge.event { background: #dbeafe; color: #1e40af; }
.event-badge.reminder { background: #fef3c7; color: #92400e; }
.event-badge.exam { background: #ede9fe; color: #5b21b6; }
</style>

<script>
function showEventModal(date, events) {
    const modal = document.getElementById('eventModal');
    const modalDate = document.getElementById('modalDate');
    const modalDayName = document.getElementById('modalDayName');
    const modalBody = document.getElementById('modalBody');
    
    const dateObj = new Date(date);
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    modalDate.textContent = dateObj.toLocaleDateString('en-US', options);
    modalDayName.textContent = events.length + ' event' + (events.length !== 1 ? 's' : '');
    
    if (events.length === 0) {
        modalBody.innerHTML = '<div style="text-align: center; padding: 40px; color: #94a3b8;"><i class="feather icon-calendar" style="font-size: 48px; margin-bottom: 16px; display: block; opacity: 0.5;"></i><p style="font-size: 16px; margin: 0;">No events on this date</p></div>';
    } else {
        modalBody.innerHTML = events.map(event => `
            <div class="event-modal-item ${event.type}">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                    <h4 style="margin: 0; font-size: 1.125rem; font-weight: 600; color: #1e293b;">${event.title}</h4>
                    <span class="event-badge ${event.type}">${event.type}</span>
                </div>
                ${event.description ? `<p style="margin: 8px 0 0 0; color: #64748b; line-height: 1.6;">${event.description}</p>` : ''}
            </div>
        `).join('');
    }
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeEventModal() {
    const modal = document.getElementById('eventModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('eventModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeEventModal();
        });
    }
});
</script>
