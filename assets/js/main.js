// Main JavaScript file
// Add any global JavaScript functionality here

function showToast(message, type = "success") {
  // Create toast container if it doesn't exist
  let toastContainer = document.getElementById("toast-container");
  if (!toastContainer) {
    toastContainer = document.createElement("div");
    toastContainer.id = "toast-container";
    toastContainer.style.cssText =
      "position: fixed; top: 24px; right: 24px; z-index: 10000; display: flex; flex-direction: column; gap: 12px;";
    document.body.appendChild(toastContainer);
  }

  const toast = document.createElement("div");
  const isError = type === "error";
  const bgColor = isError ? "#ef4444" : "#10b981";

  toast.style.cssText = `background: ${bgColor}; color: white; padding: 16px 24px; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); display: flex; align-items: center; gap: 12px; animation: slideIn 0.3s ease forwards; font-weight: 600; font-size: 14px; min-width: 300px; transition: all 0.3s ease;`;

  const icon = isError
    ? '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>'
    : '<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>';

  toast.innerHTML = `
        ${icon}
        <span style="flex: 1;">${message}</span>
    `;

  toastContainer.appendChild(toast);

  setTimeout(
    () => {
      toast.style.animation = "slideOut 0.3s ease forwards";
      setTimeout(() => toast.remove(), 300);
    },
    isError ? 5000 : 4000
  );
}

// Aliases for better semantics
function showToastSuccess(message) {
  showToast(message, "success");
}
function showToastError(message) {
  showToast(message, "error");
}

// Fallback for global use
window.showToast = showToast;
window.showToastSuccess = showToastSuccess;
window.showToastError = showToastError;

// Add keyframe animations
const toastStyle = document.createElement("style");
toastStyle.innerHTML = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
`;
document.head.appendChild(toastStyle);

document.addEventListener("DOMContentLoaded", function () {
  console.log("School Management System loaded");
});
