<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ManufactureHub - Members</title>
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
        .members-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .members-header h1 {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }
        .members-table-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .action-icons i {
            margin-right: 10px;
            cursor: pointer;
            color: #6c757d;
        }
        .action-icons i.fa-edit { color: #0d6efd; }
        .action-icons i.fa-trash-alt { color: #dc3545; }
        .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <?php $active_item = 'members'; include 'components/sidebar.php'; ?>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Top Header -->
            <?php $title = 'Members'; include 'components/top_header.php'; ?>

            <div class="container-fluid py-4 px-4">
                <div class="members-header">
                    <h1>Team Members</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                        <i class="fas fa-plus me-2"></i> Add Member
                    </button>
                </div>

                <div class="members-table-card">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="membersTableBody">
                            <!-- Members will be loaded here -->
                        </tbody>
                    </table>
                </div>

                <!-- Add Member Modal -->
                <div class="modal fade" id="addMemberModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add New Member</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="addMemberForm">
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" class="form-control" id="addName" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" id="addEmail" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" class="form-control" id="addPassword" name="password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <select class="form-select" id="addRole" name="role" required disabled>
                                            <option value="staff" selected>Staff</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" id="addStatus" name="status" required>
                                            <option value="active" selected>Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="addMember()">Add Member</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Member Modal -->
                <div class="modal fade" id="editMemberModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Member</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="editMemberForm">
                                    <input type="hidden" name="id">
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <select class="form-select" name="role" required disabled>
                                            <option value="staff" selected>Staff</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status" required>
                                            <option value="active">Active</option>
                                            <option value="disable">Disable</option>
                                            <option value="block">Block</option>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="updateMember()">Update Member</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Confirmation Modal -->
                <div class="modal fade" id="deleteMemberModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirm Delete</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this member? This action cannot be undone.</p>
                                <input type="hidden" id="deleteMemberId">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Load members when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadMembers();
        });

        function loadMembers() {
            fetch('includes/member_operations.php?action=get_members')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new TypeError("Response was not JSON");
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Response text:', text);
                            throw new Error('Invalid JSON response from server');
                        }
                    });
                })
                .then(response => {
                    const tbody = document.getElementById('membersTableBody');
                    tbody.innerHTML = '';
                    
                    if (!response.success) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">' + response.message + '</td></tr>';
                        return;
                    }
                    
                    const members = response.data;
                    if (!Array.isArray(members)) {
                        console.error('Invalid members data:', members);
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Invalid data format received</td></tr>';
                        return;
                    }
                    
                    if (members.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No members found</td></tr>';
                        return;
                    }
                    
                    members.forEach(member => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="${member.profile_photo || '../src/images/profile.jpeg'}" 
                                         alt="${member.name}" 
                                         class="member-avatar me-2">
                                    <span>${member.name}</span>
                                </div>
                            </td>
                            <td>${member.email}</td>
                            <td><span class="badge bg-primary">${member.role}</span></td>
                            <td>
                                <span class="badge ${member.status === 'active' ? 'bg-success' : 'bg-danger'}">
                                    ${member.status}
                                </span>
                            </td>
                            <td>
                                <i class="fas fa-edit action-icons" onclick="editMember(${member.id})"></i>
                                <i class="fas fa-trash-alt action-icons" onclick="showDeleteModal(${member.id})"></i>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                })
                .catch(error => {
                    console.error('Error loading members:', error);
                    const tbody = document.getElementById('membersTableBody');
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">
                        Error loading members: ${error.message}
                    </td></tr>`;
                });
        }

        async function addMember() {
            const name = document.getElementById('addName').value;
            const email = document.getElementById('addEmail').value;
            const password = document.getElementById('addPassword').value;
            const role = document.getElementById('addRole').value;
            const status = document.getElementById('addStatus').value;

            if (!name || !email || !password) {
                alert('Please fill in all required fields');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('name', name);
                formData.append('email', email);
                formData.append('password', password);
                formData.append('role', role);
                formData.append('status', status);

                const response = await fetch('includes/member_operations.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new TypeError("Response was not JSON");
                }

                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addMemberModal'));
                    modal.hide();
                    loadMembers();
                    // Clear form
                    document.getElementById('addName').value = '';
                    document.getElementById('addEmail').value = '';
                    document.getElementById('addPassword').value = '';
                    document.getElementById('addRole').value = 'staff';
                    document.getElementById('addStatus').value = 'active';
                } else {
                    alert(result.message || 'Failed to add member');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while adding the member. Please try again.');
            }
        }

        function editMember(id) {
            fetch(`includes/member_operations.php?action=get_member&id=${id}`)
                .then(response => response.json())
                .then(response => {
                    if (!response.success) {
                        throw new Error(response.message || 'Failed to load member details');
                    }
                    
                    const member = response.data;
                    const form = document.getElementById('editMemberForm');
                    
                    // Populate form fields
                    form.querySelector('input[name="id"]').value = member.id;
                    form.querySelector('input[name="name"]').value = member.name;
                    form.querySelector('input[name="email"]').value = member.email;
                    form.querySelector('select[name="role"]').value = 'staff'; // Always set to staff
                    
                    // Set status with proper case handling
                    const statusSelect = form.querySelector('select[name="status"]');
                    const currentStatus = member.status ? member.status.toLowerCase() : 'active';
                    statusSelect.value = currentStatus;
                    
                    // Show the modal
                    new bootstrap.Modal(document.getElementById('editMemberModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading member details: ' + error.message);
                });
        }

        function updateMember() {
            const form = document.getElementById('editMemberForm');
            const formData = new FormData(form);
            formData.append('action', 'update');

            fetch('includes/member_operations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editMemberModal')).hide();
                    loadMembers();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the member');
            });
        }

        function showDeleteModal(id) {
            document.getElementById('deleteMemberId').value = id;
            new bootstrap.Modal(document.getElementById('deleteMemberModal')).show();
        }

        function confirmDelete() {
            const id = document.getElementById('deleteMemberId').value;
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            fetch('includes/member_operations.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('deleteMemberModal')).hide();
                    loadMembers();
                } else {
                    throw new Error(data.message || 'Failed to delete member');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting member: ' + error.message);
            });
        }
    </script>
</body>
</html> 