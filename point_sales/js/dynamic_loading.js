document.addEventListener("DOMContentLoaded", function () {
    // Function to load categories from the server
    function loadCategories() {
        $.ajax({
            url: "fetch_categories.php", // PHP script to fetch categories
            type: "GET",
            success: function (response) {
                // Populate the categories section with the response
                $("#categories").html(response);
            },
            error: function () {
                alert("❌ Failed to load categories.");
            }
        });
    }

    // Function to load items from the server
    function loadItems(category = "") {
        $.ajax({
            url: "fetch_items.php", // PHP script to fetch items
            type: "GET",
            data: { category: category }, // Send the selected category as a parameter
            success: function (response) {
                // Populate the items section with the response
                $("#items").html(response);
            },
            error: function () {
                alert("❌ Failed to load items.");
            }
        });
    }

    // Event listener for clicking on a category
    $(document).on("click", ".category", function () {
        const category = $(this).data("category"); // Get the category from the clicked element
        loadItems(category); // Load items for the selected category
    });

    // Load all categories and items on page load
    loadCategories();
    loadItems();
});