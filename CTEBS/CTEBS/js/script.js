function openReturnModal(requestId, equipmentName) {

    document.getElementById("returnModal").classList.add("show");

    document.getElementById("returnRequestId").value = requestId;

    document.getElementById("returnEquipmentName").textContent =
        equipmentName;

}


function closeReturnModal() {

    document.getElementById("returnModal").classList.remove("show");

}

function openPenaltyModal() {

    document
        .getElementById("penaltyModal")
        .classList.add("show");

}


function closePenaltyModal() {

    document
        .getElementById("penaltyModal")
        .classList.remove("show");

}

function openReportModal() {

    document
        .getElementById("reportModal")
        .classList.add("show");

}


function closeReportModal() {

    document
        .getElementById("reportModal")
        .classList.remove("show");

}


function viewReport(details) {

    document
        .getElementById("reportDetailsText")
        .textContent = details;

    document
        .getElementById("viewReportModal")
        .classList.add("show");

}


function closeViewReportModal() {

    document
        .getElementById("viewReportModal")
        .classList.remove("show");

}
function openEditModal(button) {
    document.getElementById("edit_user_id").value = button.dataset.id;
    document.getElementById("edit_name").value = button.dataset.name;
    document.getElementById("edit_address").value = button.dataset.address;
    document.getElementById("edit_username").value = button.dataset.username;

    document.getElementById("editPersonnelModal").style.display = "flex";
}

function openResetModal(button) {
    document.getElementById("reset_user_id").value = button.dataset.id;

    document.getElementById("reset_password_description").textContent =
        "Set a new password for " + button.dataset.name + ".";

    document.getElementById("new_password").value = "";
    document.getElementById("confirm_password").value = "";

    document.getElementById("resetPasswordModal").style.display = "flex";
}

function closePersonnelModals() {
    document.getElementById("editPersonnelModal").style.display = "none";
    document.getElementById("resetPasswordModal").style.display = "none";
}

window.addEventListener("click", function(event) {
    if (event.target.classList.contains("personnel-modal")) {
        event.target.style.display = "none";
    }
});

document.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        closePersonnelModals();
    }
});
function openQrModal(button) {
    const code = button.dataset.code;
    const qrContainer = document.getElementById("qrImage");

    document.getElementById("qrEquipmentName").textContent =
        button.dataset.name;

    document.getElementById("qrEquipmentCategory").textContent =
        button.dataset.category;

    document.getElementById("qrEquipmentId").textContent =
        button.dataset.id;

    document.getElementById("qrCodeText").textContent = code;

    qrContainer.replaceChildren();

    document.getElementById("qrModal").style.display = "flex";

    if (typeof QRCode === "undefined") {
        qrContainer.textContent =
            "QR library could not load. Check your internet connection and reload the page.";
        return;
    }

    new QRCode(qrContainer, {
        text: code,
        width: 190,
        height: 190,
        colorDark: "#173f2e",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
}

function closeQrModal() {
    document.getElementById("qrModal").style.display = "none";
}

function printQrLabel() {
    window.print();
}

window.addEventListener("click", function(event) {
    if (event.target.id === "qrModal") {
        closeQrModal();
    }
});

document.addEventListener("keydown", function(event) {
    if (event.key === "Escape") {
        closeQrModal();
    }
});

function toggleProfileMenu(button) {
    const menu = button.nextElementSibling;
    const isOpen = menu.classList.toggle("show");

    button.setAttribute("aria-expanded", isOpen);
}

document.addEventListener("click", function (event) {
    document.querySelectorAll(".profile-dropdown").forEach(function (dropdown) {
        if (!dropdown.contains(event.target)) {
            dropdown.querySelector(".profile-dropdown-menu")
                .classList.remove("show");

            dropdown.querySelector(".profile-dropdown-toggle")
                .setAttribute("aria-expanded", "false");
        }
    });
});
document.addEventListener('DOMContentLoaded', function () {
    const equipmentSelect = document.getElementById('equipment_id');
    const quantityInput = document.getElementById('borrow_quantity');
    const quantityHelp = document.getElementById('quantity-help');

    function updateQuantityLimit() {
        const option = equipmentSelect.options[
            equipmentSelect.selectedIndex
        ];

        const available = parseInt(
            option.getAttribute('data-available') || '0',
            10
        );

        if (available > 0) {
            quantityInput.max = available;

            quantityHelp.textContent =
                'Available quantity: ' + available;
        } else {
            quantityInput.max = 1;

            quantityHelp.textContent =
                'Please select available equipment.';
        }
    }

    equipmentSelect.addEventListener(
        'change',
        updateQuantityLimit
    );

    updateQuantityLimit();
});