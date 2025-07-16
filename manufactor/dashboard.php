<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ManufactureHub Dashboard</title>
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

        .overview-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .overview-icon {
            font-size: 2rem;
            margin-right: 15px;
            padding: 15px;
            border-radius: 8px;
            background-color: #e0e7ff; /* Light blue background */
            color: #2563eb; /* Blue icon color */
        }
        .overview-card .card-title {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .overview-card .card-value {
            font-size: 1.8rem;
            font-weight: bold;
        }
        /* Revert to specific colors for overview icons */
        .overview-card:nth-child(2) .overview-icon { color: #22C55E; background-color: #F0FDF4; }
        .overview-card:nth-child(3) .overview-icon { color: #A855F7; background-color: #FAF5FF; }
        .overview-card:nth-child(4) .overview-icon { color: #EAB308; background-color: #FEFCE8; }

        .recent-orders-card, .recent-activity-card {
             background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        .recent-orders-card h5, .recent-activity-card h5 {
            font-weight: bold;
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
        .status-completed { background-color: #d1fae5; color: #065f46; }
        .status-pending { background-color: #fffbeb; color: #92400e; }
        .status-processing { background-color: #e0e7ff; color: #1e40af; }

        .recent-activity-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .activity-icon {
            font-size: 1.2rem;
            margin-right: 15px;
            color: #2563eb;
        }
         .activity-details h6 {
             margin-bottom: 2px;
         }
         .activity-details small {
             color: #6c757d;
         }
    </style>
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <?php $active_item = 'dashboard'; include 'components/sidebar.php'; ?>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Top Header -->
             <?php include 'components/top_header.php'; ?>

             <div class="container-fluid py-4 px-4">
                <div class="row">
                    <div class="col-md-3">
                        <div class="overview-card">
                            <i class="fas fa-box overview-icon"></i>
                            <div>
                                <div class="card-title">Total Orders</div>
                                <div class="card-value">1,254</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="overview-card">
                            <i class="fas fa-dollar-sign overview-icon"></i>
                            <div>
                                <div class="card-title">Revenue</div>
                                <div class="card-value">$84,245</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="overview-card">
                            <i class="fas fa-cube overview-icon"></i>
                            <div>
                                <div class="card-title">Products</div>
                                <div class="card-value">324</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="overview-card">
                            <i class="fas fa-users overview-icon"></i>
                            <div>
                                <div class="card-title">Customers</div>
                                <div class="card-value">1,821</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="recent-orders-card">
                            <h5>Recent Orders</h5>
                            <table class="table table-borderless">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>#ORD-2891</td>
                                        <td>Sarah Johnson</td>
                                        <td><span class="status-badge status-completed">Completed</span></td>
                                        <td>$459.00</td>
                                    </tr>
                                    <tr>
                                        <td>#ORD-2892</td>
                                        <td>Michael Brown</td>
                                        <td><span class="status-badge status-pending">Pending</span></td>
                                        <td>$289.00</td>
                                    </tr>
                                     <tr>
                                        <td>#ORD-2893</td>
                                        <td>David Wilson</td>
                                        <td><span class="status-badge status-processing">Processing</span></td>
                                        <td>$799.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-4">
                         <div class="recent-activity-card">
                             <h5>Recent Activity</h5>
                             <div class="recent-activity-item">
                                 <i class="fas fa-shopping-cart activity-icon"></i>
                                 <div class="activity-details">
                                     <h6>New order received</h6>
                                     <small>2 minutes ago</small>
                                 </div>
                             </div>
                             <div class="recent-activity-item">
                                  <i class="fas fa-check-circle activity-icon"></i>
                                  <div class="activity-details">
                                     <h6>Order #2891 completed</h6>
                                     <small>15 minutes ago</small>
                                  </div>
                             </div>
                             <div class="recent-activity-item">
                                  <i class="fas fa-box-open activity-icon"></i>
                                  <div class="activity-details">
                                     <h6>Inventory update: +50 units</h6>
                                     <small>1 hour ago</small>
                                  </div>
                             </div>
                         </div>
                    </div>
                </div>


             </div>
         </div>
     </div>

     <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 