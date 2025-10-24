// Toggle menu visibility
let menu = document.querySelector('.header .menu');

document.querySelector('#menu-btn').onclick = () => {
   menu.classList.toggle('active');
}

window.onscroll = () => {
   menu.classList.remove('active');
}

// Digit-limiting fix for number inputs using data-maxlength
document.querySelectorAll('input[type="number"][data-maxlength]').forEach(input => {
   input.addEventListener('input', () => {
      const maxLength = parseInt(input.dataset.maxlength);
      let [integerPart, decimalPart] = input.value.split('.');
      
      if (integerPart.length > maxLength) {
         integerPart = integerPart.slice(0, maxLength);
         input.value = decimalPart ? `${integerPart}.${decimalPart}` : integerPart;
      }
   });
});

// Image gallery switching
document.querySelectorAll('.view-property .details .thumb .small-images img').forEach(img => {
   img.onclick = () => {
      const src = img.getAttribute('src');
      document.querySelector('.view-property .details .thumb .big-image img').src = src;
   }
});

// FAQ toggle
document.querySelectorAll('.faq .box-container .box h3').forEach(heading => {
   heading.onclick = () => {
      heading.parentElement.classList.toggle('active');
   }
});

// Dashboard Enhancements ==============================================

// Card hover effects
document.querySelectorAll('.summary-card, .chart-card, .list-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
        card.style.transform = 'translateY(-5px)';
        card.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
    });
    
    card.addEventListener('mouseleave', () => {
        card.style.transform = '';
        card.style.boxShadow = '';
    });
});

// Export modal functionality
document.addEventListener('DOMContentLoaded', function() {
    // Close modal
    const closeModal = document.querySelector('.close-modal');
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            document.getElementById('exportModal').style.display = 'none';
        });
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('exportModal');
        if (modal && event.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Dashboard card animations
    const cards = document.querySelectorAll('.summary-card, .chart-card, .list-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = `opacity 0.5s ease, transform 0.5s ease ${index * 0.1}s`;
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100);
    });

    // Real-time data refresh (example)
    if (document.querySelector('.dashboard-grid')) {
        setInterval(() => {
            // This is just a simulation - in a real app you would fetch new data
            const activityItems = document.querySelectorAll('.activity-item');
            if (activityItems.length > 0) {
                const firstItem = activityItems[0];
                firstItem.style.opacity = '0';
                setTimeout(() => {
                    firstItem.remove();
                }, 300);
            }
        }, 30000); // Refresh every 30 seconds
    }
});

// Notification system
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Error handling for charts
function handleChartError(chartId, errorMessage) {
    const canvas = document.getElementById(chartId);
    if (canvas) {
        canvas.parentElement.innerHTML = `
            <div class="chart-error">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${errorMessage}</p>
            </div>
        `;
    }
}

// Responsive adjustments
function handleResponsive() {
    const dashboardGrid = document.querySelector('.dashboard-grid');
    if (dashboardGrid && window.innerWidth < 768) {
        dashboardGrid.style.gridTemplateColumns = '1fr';
    }
}

window.addEventListener('resize', handleResponsive);
handleResponsive();

// Enhanced export functionality
document.querySelectorAll('.export-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const type = this.dataset.type;
        const format = this.dataset.format;
        const modal = document.getElementById('exportModal');
        const progress = document.querySelector('.progress');
        const downloadLink = document.getElementById('downloadLink');
        
        if (modal && progress && downloadLink) {
            modal.style.display = 'block';
            progress.style.width = '0%';
            
            // Simulate processing
            let width = 0;
            const interval = setInterval(() => {
                width += 5;
                progress.style.width = width + '%';
                
                if (width >= 100) {
                    clearInterval(interval);
                    prepareExportData(type, format, downloadLink);
                    downloadLink.style.display = 'inline-block';
                }
            }, 50);
        }
    });
});

function prepareExportData(type, format, downloadLink) {
    // In a real implementation, you would fetch fresh data here
    // This is just a simulation using data already in the DOM
    
    let data, filename, mimeType;
    const now = new Date().toISOString().split('T')[0];
    
    switch(type) {
        case 'property-types':
            data = {};
            document.querySelectorAll('#propertyTypeChart').forEach(chart => {
                if (chart.__chartjs) {
                    data = chart.__chartjs.config.data;
                }
            });
            filename = `property_types_${now}`;
            break;
        case 'user-registrations':
            data = [];
            document.querySelectorAll('#userRegistrationChart').forEach(chart => {
                if (chart.__chartjs) {
                    const labels = chart.__chartjs.config.data.labels;
                    const values = chart.__chartjs.config.data.datasets[0].data;
                    data = labels.map((label, i) => ({ date: label, count: values[i] }));
                }
            });
            filename = `user_registrations_${now}`;
            break;
        default:
            data = { error: "Data export not implemented for this type" };
            filename = `export_${now}`;
    }
    
    if (format === 'csv') {
        mimeType = 'text/csv';
        let csv = '';
        
        if (Array.isArray(data)) {
            // Array data
            const headers = Object.keys(data[0] || {});
            csv += headers.join(',') + '\n';
            
            data.forEach(item => {
                csv += headers.map(header => {
                    return `"${item[header]}"`;
                }).join(',') + '\n';
            });
        } else if (data.labels && data.datasets) {
            // Chart.js data structure
            csv = "Label,Value\n";
            data.labels.forEach((label, i) => {
                csv += `"${label}","${data.datasets[0].data[i]}"\n`;
            });
        } else {
            // Object data
            csv = "Key,Value\n";
            for (const [key, value] of Object.entries(data)) {
                csv += `"${key}","${value}"\n`;
            }
        }
        
        const blob = new Blob([csv], { type: mimeType });
        downloadLink.href = URL.createObjectURL(blob);
        downloadLink.download = `${filename}.csv`;
    } else {
        mimeType = 'application/json';
        const json = JSON.stringify(data, null, 2);
        const blob = new Blob([json], { type: mimeType });
        downloadLink.href = URL.createObjectURL(blob);
        downloadLink.download = `${filename}.json`;
    }
}