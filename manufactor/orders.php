<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ManufactureHub - Orders</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        #wrapper {
            display: flex;
        }
        #page-content-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .orders-header {
            display: flex;
            justify-content: space-between; /* Separate heading and search area */
            align-items: center;
            margin-bottom: 20px;
        }
         .orders-header h1 { /* Style for the heading */
            font-size: 1.8rem;
            font-weight: bold;
            color: #333; /* Dark grey color */
        }
        .search-input-container {
            position: relative;
            width: 520px; /* Set specific width */
        }
        .search-input {
            padding-left: 35px; /* Make space for the icon */
        }
        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa; /* Adjust color as needed */
        }
        .orders-table-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        .table th {
             color: #6c757d;
             font-weight: 500;
         }
         .status-badge {
             padding: 5px 10px;
             border-radius: 15px;
             font-size: 0.8rem;
             font-weight: bold;
         }
        .status-new { background-color: #d1fae5; color: #065f46; } /* Green */
        .status-pending { background-color: #fffbeb; color: #92400e; } /* Yellow */
        .status-shipped { background-color: #fee2e2; color: #991b1b; } /* Red */
         .table-product-icon {
             margin-right: 10px;
             color: #6c757d;
         }
    </style>
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <?php $active_item = 'orders'; include 'components/sidebar.php'; ?>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Top Header -->
            <?php $title = 'Orders'; include 'components/top_header.php'; ?>

            <div class="container-fluid py-4 px-4">

                <div class="orders-header">
                    <h1>Orders</h1>
                    <div class="d-flex align-items-center" style="flex-grow: 1; justify-content: center;">
                         <div class="search-input-container me-3">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" class="form-control search-input" placeholder="Search order.">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                     <div>
                          <div class="dropdown d-inline-block me-2">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButtonCategories" data-bs-toggle="dropdown" aria-expanded="false">
                              All Categories
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButtonCategories">
                              <li><a class="dropdown-item" href="#">Electronics</a></li>
                              <li><a class="dropdown-item" href="#">Clothing</a></li>
                            </ul>
                          </div>
                           <div class="dropdown d-inline-block">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButtonSort" data-bs-toggle="dropdown" aria-expanded="false">
                              Sort by
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButtonSort">
                              <li><a class="dropdown-item" href="#">Name</a></li>
                              <li><a class="dropdown-item" href="#">Price</a></li>
                            </ul>
                          </div>
                     </div>
                     <div>
                          <i class="fas fa-question-circle me-2"></i>
                          <i class="fas fa-list"></i>
                     </div>
                </div>

                <div class="orders-table-card">
                    <table class="table table-borderless">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>


            </div>
        </div>
    </div>

     <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 