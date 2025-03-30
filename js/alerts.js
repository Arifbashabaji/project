document.addEventListener("DOMContentLoaded", function () {
  const alertDashboard = document.querySelector(".alert-dashboard");
  const alertList = document.querySelector(".alert-list");
  const alertFilters = document.querySelector(".alert-filters");
  const paginationContainer = document.querySelector(".alert-pagination");
  const alertDetailsModal = document.getElementById("alertDetailsModal"); // Get the modal
  const modalCloseButtons = document.querySelectorAll(".close-btn"); // Get close buttons
  let currentPage = 1;
  const limitPerPage = 10; // Set alerts per page

  // --- Fetch Alert Summary ---
  function fetchAlertSummary() {
    fetch("/api/alerts/summary")
      .then((response) => response.json())
      .then((data) => {
        // Update the alert dashboard with the summary data
        const criticalCard = alertDashboard
          .querySelector(".alert-critical")
          .closest(".alert-card");
        const warningCard = alertDashboard
          .querySelector(".alert-warning")
          .closest(".alert-card");
        const infoCard = alertDashboard
          .querySelector(".alert-info")
          .closest(".alert-card");

        criticalCard.querySelector(".alert-count .alert-critical").textContent =
          data.critical.total;
        criticalCard.querySelector(".notification-badge").textContent =
          data.critical.unread;

        warningCard.querySelector(".alert-count .alert-warning").textContent =
          data.warning.total;
        warningCard.querySelector(".notification-badge").textContent =
          data.warning.unread;

        infoCard.querySelector(".alert-count .alert-info").textContent =
          data.info.total;
        infoCard.querySelector(".notification-badge").textContent =
          data.info.unread;
      })
      .catch((error) => console.error("Error fetching alert summary:", error));
  }

  // --- Fetch Alerts ---
  function fetchAlerts(page = 1) {
    const alertType = alertFilters.querySelector(
      '.filter-select[aria-label="Alert Type"]'
    ).value;
    const location = alertFilters.querySelector(
      '.filter-select[aria-label="Location"]'
    ).value;
    const date = alertFilters.querySelector('.filter-input[type="date"]').value;
    const licensePlate = alertFilters.querySelector(
      '.filter-input[type="text"]'
    ).value;

    // Build the query string
    const params = new URLSearchParams({
      type: alertType,
      location: location,
      date: date,
      license_plate: licensePlate,
      page: page,
      limit: limitPerPage,
    });

    fetch(`/api/alerts?${params.toString()}`)
      .then((response) => response.json())
      .then((data) => {
        renderAlerts(data.alerts);
        renderPagination(data.total, page, limitPerPage); // Render pagination
      })
      .catch((error) => console.error("Error fetching alerts:", error));
  }

  // --- Render Alerts ---
  function renderAlerts(alerts) {
    alertList.innerHTML = ""; // Clear existing alerts

    alerts.forEach((alert) => {
      const alertItem = document.createElement("div");
      alertItem.classList.add("alert-item", alert.type);
      alertItem.innerHTML = `
                <div class="alert-item-icon ${alert.type}">
                    <i class="fas ${
                      alert.type === "critical"
                        ? "fa-exclamation-circle"
                        : alert.type === "warning"
                        ? "fa-exclamation-triangle"
                        : "fa-info-circle"
                    }"></i>
                </div>
                <div class="alert-item-content">
                    <div class="alert-item-header">
                        <div class="alert-item-title">${alert.title}</div>
                        <div class="alert-item-time">${alert.time}</div>
                    </div>
                    <div class="alert-item-description">${
                      alert.description
                    }</div>
                    <div class="alert-item-actions">
                        <button class="alert-item-details-btn" data-id="${
                          alert.id
                        }">View Details</button>
                         <button class="alert-item-resolve-btn" data-id="${
                           alert.id
                         }">Resolve</button>
                    </div>
                </div>
            `;
      alertList.appendChild(alertItem);
    });
  }
  // --- Render Pagination ---
  function renderPagination(totalAlerts, currentPage, limitPerPage) {
    const totalPages = Math.ceil(totalAlerts / limitPerPage);
    paginationContainer.innerHTML = "";

    // Previous Button
    const prevButton = document.createElement("div");
    prevButton.classList.add("pagination-btn");
    if (currentPage === 1) {
      prevButton.classList.add("disabled"); // Disable if on the first page.
    }
    prevButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevButton.addEventListener("click", () => {
      if (currentPage > 1) {
        fetchAlerts(currentPage - 1);
      }
    });
    paginationContainer.appendChild(prevButton);

    // Page buttons
    for (let i = 1; i <= totalPages; i++) {
      const pageButton = document.createElement("div");
      pageButton.classList.add("pagination-btn");
      pageButton.textContent = i;
      if (i === currentPage) {
        pageButton.classList.add("active");
      }
      pageButton.addEventListener("click", () => {
        fetchAlerts(i);
      });
      paginationContainer.appendChild(pageButton);
    }

    // Next Button
    const nextButton = document.createElement("div");
    nextButton.classList.add("pagination-btn");
    if (currentPage === totalPages) {
      nextButton.classList.add("disabled");
    }
    nextButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextButton.addEventListener("click", () => {
      if (currentPage < totalPages) {
        fetchAlerts(currentPage + 1);
      }
    });
    paginationContainer.appendChild(nextButton);
  }

  // --- Event listeners for filter changes ---

  alertFilters.querySelector(".filter-button").addEventListener("click", () => {
    fetchAlerts(); // Fetch alerts with current filter values
  });

  alertFilters
    .querySelector(".filter-button.reset")
    .addEventListener("click", () => {
      // Reset filter controls to default values
      alertFilters
        .querySelectorAll(".filter-select")
        .forEach((select) => (select.value = "all"));
      alertFilters
        .querySelectorAll(".filter-input")
        .forEach((input) => (input.value = ""));
      fetchAlerts(); // Fetch alerts with default filters
    });

  // --- Resolve button event listener (delegated) ---
  alertList.addEventListener("click", (event) => {
    if (event.target.classList.contains("alert-item-resolve-btn")) {
      const alertId = event.target.dataset.id;

      fetch(`/api/alerts/${alertId}/resolve`, {
        method: "PUT", // Or PATCH, depending on your API design
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error("Failed to resolve alert");
          }
          return response.json();
        })
        .then((data) => {
          // Remove the alert item from the list, or update its appearance
          event.target.closest(".alert-item").remove();
          // You might also want to refresh the alert summary
          fetchAlertSummary();
        })
        .catch((error) => console.error("Error resolving alert:", error));
    }
  });

  // --- View Details button event listener (delegated) ---
  alertList.addEventListener("click", (event) => {
    if (event.target.classList.contains("alert-item-details-btn")) {
      const alertId = event.target.dataset.id;
      fetchAlertDetails(alertId);
    }
  });

  // --- Fetch Alert Details ---
  function fetchAlertDetails(alertId) {
    fetch(`/api/alerts/${alertId}/details`)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
      })
      .then((alert) => {
        // Populate the modal with the alert details
        document.getElementById("detail-id").textContent = alert.id;
        document.getElementById("detail-type").textContent = alert.type;
        document.getElementById("detail-title").textContent = alert.title;
        document.getElementById("detail-licensePlate").textContent =
          alert.licensePlate;
        document.getElementById("detail-location").textContent = alert.location;
        document.getElementById("detail-time").textContent = alert.time;
        document.getElementById("detail-status").textContent = alert.status;
        document.getElementById("detail-description").textContent =
          alert.description;

        // Vehicle details (conditional rendering)
        const vehicleInfoRow = document.getElementById("detail-vehicle-info");
        const vehicleMakeModel = document.getElementById(
          "detail-vehicle-make-model"
        );
        const vehicleColor = document.getElementById("detail-vehicle-color"); // Get the color span

        if (alert.vehicle && alert.vehicle.make && alert.vehicle.model) {
          vehicleMakeModel.textContent = `${alert.vehicle.make} ${alert.vehicle.model}`;
          vehicleColor.textContent = alert.vehicle.color; // Set the color
          vehicleInfoRow.style.display = "flex"; // Show the row
        } else {
          vehicleInfoRow.style.display = "none"; // Hide the row if no vehicle info
        }

        // Vehicle image (conditional rendering)
        const vehicleImageContainer = document.getElementById(
          "detail-vehicle-image-container"
        );
        const vehicleImage = document.getElementById("detail-vehicle-image");
        if (alert.vehicle && alert.vehicle.imagePath) {
          vehicleImage.src = alert.vehicle.imagePath;
          vehicleImageContainer.style.display = "block"; // Show container
        } else {
          vehicleImageContainer.style.display = "none"; // Hide container
        }

        // Detection image (conditional rendering)
        const detectionImageContainer = document.getElementById(
          "detail-detection-image-container"
        );
        const detectionImage = document.getElementById(
          "detail-detection-image"
        );

        if (alert.detectionImage) {
          detectionImage.src = alert.detectionImage;
          detectionImageContainer.style.display = "block"; // Show
        } else {
          detectionImageContainer.style.display = "none"; // Hide
        }

        // Show the modal
        alertDetailsModal.style.display = "block";
      })
      .catch((error) => {
        console.error("Error fetching alert details:", error);
        alert("Failed to fetch alert details."); // User-friendly error
      });
  }

  // --- Close modal event listeners ---
  modalCloseButtons.forEach((button) => {
    button.addEventListener("click", () => {
      alertDetailsModal.style.display = "none";
    });
  });

  // Close modal if the user clicks outside of it
  window.addEventListener("click", (event) => {
    if (event.target === alertDetailsModal) {
      alertDetailsModal.style.display = "none";
    }
  });

  // --- Initial fetch ---
  fetchAlertSummary();
  fetchAlerts();
});
