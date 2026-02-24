document.addEventListener("DOMContentLoaded", function () {
    // Function to update the balance (change)
    function updateBalance() {
        const cash = parseFloat(document.getElementById("cash").value) || 0;
        const total = parseFloat(document.getElementById("total").value) || 0;
        const balance = cash - total;
        const balanceField = document.getElementById("balance");

        balanceField.value = balance.toFixed(2);
        balanceField.style.color = balance < 0 ? "red" : "black";
    }

    // Event listener for cash input
    document.getElementById("cash").addEventListener("input", updateBalance);

    // Event listener for checkout button
    document.getElementById("checkout").addEventListener("click", function () {
        const total = parseFloat(document.getElementById("total").value) || 0;
        const cash = parseFloat(document.getElementById("cash").value) || 0;

        if (total === 0) {
            alert("❌ No items in the bill!");
            return;
        }

        if (cash < total) {
            alert("❌ Insufficient cash amount!");
            return;
        }

        // Gather order items
        const orderItems = [];
        document.querySelectorAll("#order-table tbody tr").forEach((row) => {
            const name = row.querySelector("td:nth-child(1)").textContent.trim();
            const price = parseFloat(row.querySelector("td:nth-child(2)").textContent.trim());
            const qty = parseInt(row.querySelector("td:nth-child(3)").textContent.trim());

            if (name && !isNaN(price) && !isNaN(qty)) {
                orderItems.push({ name: name, price: price, quantity: qty });
            }
        });

        // Check if there are items in the order
        if (orderItems.length === 0) {
            alert("❌ No items detected in the bill!");
            return;
        }

        // Gather all data into a single object
        const orderData = {
            items: orderItems,
            total: total,
            cash: cash,
            order_type: document.getElementById("order-type").value,
        };

        // Send the data to the PHP backend
        fetch("save_sales.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(orderData),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    alert("✅ Order saved successfully!");
                    resetBillingForm(); // Clear the form
                } else {
                    alert("❌ Error: " + data.error);
                }
            })
            .catch((error) => {
                console.error("Fetch Error:", error);
                alert("❌ An error occurred while processing the order.");
            });
    });

    // Function to reset the billing form after a successful checkout
    function resetBillingForm() {
        document.querySelector("#order-table tbody").innerHTML = "";
        document.getElementById("total").value = "0.00";
        document.getElementById("cash").value = "0.00";
        document.getElementById("balance").value = "0.00";
        document.getElementById("order-type").value = "Dine-In";
    }
});