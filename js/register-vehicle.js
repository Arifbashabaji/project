document.addEventListener("DOMContentLoaded", function () {
  const registrationForm = document.getElementById("vehicleRegistrationForm");
  const recentVehiclesTableBody = document.querySelector(".recent-table tbody");
  const cancelButton = document.querySelector(".btn-outline");

  // --- Function to fetch and display recent registrations ---
  function fetchRecentRegistrations() {
    fetch("/api/recent-registrations") // Use the correct API endpoint
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        recentVehiclesTableBody.innerHTML = ""; // Clear existing table rows

        data.forEach((vehicle) => {
          const row = document.createElement("tr");
          row.setAttribute("data-id", vehicle.id); // Add the data-id attribute
          row.innerHTML = `
                        <td>${vehicle.license_plate}</td>
                        <td>${vehicle.owner}</td>
                        <td>${vehicle.vehicle}</td>
                        <td>${vehicle.access_level}</td>
                        <td><span class="status-badge status-active">${vehicle.status}</span></td>
                        <td>
                            <div class="action-btn edit-btn"><i class="fas fa-pen"></i></div>
                            <div class="action-btn delete-btn"><i class="fas fa-trash"></i></div>
                        </td>
                    `;
          recentVehiclesTableBody.appendChild(row);
        });
      })
      .catch((error) => {
        console.error("Error fetching recent registrations:", error);
        // Display an error message to the user, perhaps in a dedicated error area
      });
  }

  // --- Initial fetch of recent registrations ---
  fetchRecentRegistrations();

  // --- Handle form submission ---
  registrationForm.addEventListener("submit", function (event) {
    event.preventDefault(); // Prevent default form submission

    // --- Collect form data ---
    const formData = new FormData(); // Use FormData for file uploads
    formData.append(
      "licensePlate",
      document.getElementById("licensePlate").value
    );
    formData.append(
      "vehicleType",
      document.getElementById("vehicleType").value
    );
    formData.append("make", document.getElementById("make").value);
    formData.append("model", document.getElementById("model").value);
    formData.append("year", document.getElementById("year").value);
    formData.append("color", document.getElementById("color").value);
    formData.append("ownerName", document.getElementById("ownerName").value);
    formData.append("ownerID", document.getElementById("ownerID").value);
    formData.append(
      "contactPhone",
      document.getElementById("contactPhone").value
    );
    formData.append(
      "contactEmail",
      document.getElementById("contactEmail").value
    );
    formData.append(
      "accessLevel",
      document.getElementById("accessLevel").value
    );
    formData.append("validUntil", document.getElementById("validUntil").value);
    formData.append("notes", document.getElementById("notes").value);

    // Handle checkboxes (FormData automatically handles 'on'/'off')
    formData.append("mainGate", document.getElementById("mainGate").checked);
    formData.append(
      "employeeParking",
      document.getElementById("employeeParking").checked
    );
    formData.append(
      "visitorParking",
      document.getElementById("visitorParking").checked
    );
    formData.append(
      "deliveryArea",
      document.getElementById("deliveryArea").checked
    );
    formData.append(
      "restrictedZone",
      document.getElementById("restrictedZone").checked
    );

    // --- Handle file uploads ---
    const vehicleImageInput = document.getElementById("vehicleImage");
    if (vehicleImageInput.files.length > 0) {
      formData.append("vehicleImage", vehicleImageInput.files[0]);
    }
    const plateImageInput = document.getElementById("plateImage");
    if (plateImageInput.files.length > 0) {
      formData.append("plateImage", plateImageInput.files[0]);
    }

    // --- Send data to the backend ---
    fetch("/api/register-vehicle", {
      // Use the correct API endpoint
      method: "POST",
      body: formData, // Send the FormData object
      // No need to set Content-Type: application/json when using FormData
    })
      .then((response) => {
        if (!response.ok) {
          return response.json().then((data) => {
            throw data;
          }); // Get error details.
        }
        return response.json();
      })
      .then((data) => {
        // --- Handle success ---
        console.log("Success:", data);
        // Display a success message to the user (e.g., using an alert or a dedicated message area)
        alert(data.message); // Simple alert.

        // Clear the form
        registrationForm.reset();

        // Refresh the recent registrations table
        fetchRecentRegistrations();
      })
      .catch((error) => {
        // --- Handle errors ---
        console.error("Error:", error);
        let errorMessage = "An error occurred. Please try again."; // A default message.

        if (error && error.message) {
          // If we have detailed errors from our backend.
          errorMessage = error.message;
          //If you have error.errors object, build the error message using that.
          if (error.errors) {
            errorMessage += "<ul>";
            for (const field in error.errors) {
              errorMessage += `<li>${field}: ${error.errors[field]}</li>`;
            }
            errorMessage += "</ul>";
          }
        }
        alert(errorMessage);
      });
  });

  // --- Handle "Choose File" button clicks for image uploads ---
  document.querySelectorAll(".upload-btn").forEach((button) => {
    button.addEventListener("click", function () {
      // Find the associated file input (it's the next element)
      const fileInput = this.nextElementSibling;
      if (
        fileInput &&
        fileInput.tagName === "INPUT" &&
        fileInput.type === "FILE"
      ) {
        fileInput.click(); // Trigger the file input's click event
      }
    });
  });

  // --- Cancel button functionality ---
  cancelButton.addEventListener("click", () => {
    window.location.href = "index.html"; // Redirect to the home page
  });

  // --- Edit and Delete button event listeners (delegated) ---
  recentVehiclesTableBody.addEventListener("click", function (event) {
    const target = event.target;

    // --- Handle Delete ---
    if (target.closest(".delete-btn")) {
      // Check if the clicked element or its parent is a delete button
      const row = target.closest("tr");
      // Get the license plate from the row (assuming it's in the first cell)
      const licensePlate = row.querySelector("td:first-child").textContent;

      // Confirm deletion with the user
      if (
        confirm(
          `Are you sure you want to delete vehicle with license plate ${licensePlate}?`
        )
      ) {
        // Send DELETE request to the backend (implementation depends on your API)
        // You'll need to get the vehicle ID.  This is a good example of why
        // it's helpful to include the ID in the table data, even if it's hidden.
        //  Let's assume you add a data-id attribute to each row:
        //  <tr data-id="${vehicle.id}">

        const vehicleId = row.dataset.id; // Get the ID from the data-id attribute.

        if (!vehicleId) {
          alert("Could not find the vehicle to delete.");
          return; // Exit if no ID found.
        }

        fetch(`/api/delete-vehicle/${vehicleId}`, {
          // Replace with your actual delete endpoint
          method: "DELETE",
        })
          .then((response) => {
            if (!response.ok) {
              throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
          })
          .then((data) => {
            // Remove the row from the table on success
            row.remove();
            alert(data.message); // Show success.
          })
          .catch((error) => {
            console.error("Error deleting vehicle:", error);
            alert("Could not delete vehicle."); // Show error to user.
          });
      }
    }

    // --- Handle Edit (Placeholder - Requires edit form) ---
    if (target.closest(".edit-btn")) {
      const row = target.closest("tr");
      const vehicleId = row.dataset.id;
      //  Implement edit functionality (likely redirect to an edit page or show a modal)
      alert("Edit functionality not yet implemented. Vehicle ID: " + vehicleId); // Placeholder
    }
  });
});
