    <!-- Edit User Modal -->
    <div class="admin-modal" id="editUserModal">
        <div class="admin-modal-card animate__animated animate__zoomIn">
            <div class="admin-modal-header">
                <h3 style="margin:0; color:#fff; font-size:1.15rem;">Edycja Profilu Użytkownika</h3>
                <span class="admin-modal-close" onclick="closeModal('editUserModal')">✕</span>
            </div>
            <form action="" method="POST">
                <?= SecurityEnterprise::csrfField() ?>
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="target_user_id" id="editUserTargetId">
                
                <div class="form-group" style="margin-bottom:14px;">
                    <label style="display:block; font-size:0.8rem; color:#94a3b8; margin-bottom:4px;">Nazwa Użytkownika</label>
                    <input type="text" name="username" id="editUserUsername" class="form-control" style="background:#0f172a; color:#fff; border:1px solid rgba(255,255,255,0.08); border-radius:8px; width:100%; padding:10px; box-sizing:border-box;" required>
                </div>
                
                <div class="form-group" style="margin-bottom:14px;">
                    <label style="display:block; font-size:0.8rem; color:#94a3b8; margin-bottom:4px;">E-mail</label>
                    <input type="email" name="email" id="editUserEmail" class="form-control" style="background:#0f172a; color:#fff; border:1px solid rgba(255,255,255,0.08); border-radius:8px; width:100%; padding:10px; box-sizing:border-box;" required>
                </div>
                
                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; font-size:0.8rem; color:#94a3b8; margin-bottom:4px;">Klasa (Level)</label>
                    <input type="text" name="class_level" id="editUserClass" class="form-control" placeholder="np. 4TI, 1LO" style="background:#0f172a; color:#fff; border:1px solid rgba(255,255,255,0.08); border-radius:8px; width:100%; padding:10px; box-sizing:border-box;">
                </div>
                
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-secondary" style="width:auto; padding:8px 16px; cursor:pointer;" onclick="closeModal('editUserModal')">Anuluj</button>
                    <button type="submit" class="btn btn-primary" style="width:auto; padding:8px 16px; cursor:pointer;">Zapisz zmiany</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="admin-modal" id="addUserModal">
        <div class="admin-modal-card animate__animated animate__zoomIn">
            <div class="admin-modal-header">
                <h3 style="margin:0; color:#fff; font-size:1.15rem;">Dodaj Nowego Użytkownika</h3>
                <span class="admin-modal-close" onclick="closeModal('addUserModal')">✕</span>
            </div>
            <form action="" method="POST">
                <?= SecurityEnterprise::csrfField() ?>
                <input type="hidden" name="action" value="add_user">
                
                <div class="form-group" style="margin-bottom:14px;">
                    <label style="display:block; font-size:0.8rem; color:#94a3b8; margin-bottom:4px;">Nazwa Użytkownika</label>
                    <input type="text" name="username" class="form-control" style="background:#0f172a; color:#fff; border:1px solid rgba(255,255,255,0.08); border-radius:8px; width:100%; padding:10px; box-sizing:border-box;" required>
                </div>
                
                <div class="form-group" style="margin-bottom:14px;">
                    <label style="display:block; font-size:0.8rem; color:#94a3b8; margin-bottom:4px;">E-mail</label>
                    <input type="email" name="email" class="form-control" style="background:#0f172a; color:#fff; border:1px solid rgba(255,255,255,0.08); border-radius:8px; width:100%; padding:10px; box-sizing:border-box;" required>
                </div>

                <div class="form-group" style="margin-bottom:14px;">
                    <label style="display:block; font-size:0.8rem; color:#94a3b8; margin-bottom:4px;">Hasło</label>
                    <input type="password" name="password" class="form-control" style="background:#0f172a; color:#fff; border:1px solid rgba(255,255,255,0.08); border-radius:8px; width:100%; padding:10px; box-sizing:border-box;" required>
                </div>

                <div class="form-group" style="margin-bottom:14px;">
                    <label style="display:block; font-size:0.8rem; color:#94a3b8; margin-bottom:4px;">Typ konta</label>
                    <select name="type" style="background:#0f172a; color:#fff; border:1px solid rgba(255,255,255,0.08); border-radius:8px; width:100%; padding:10px; box-sizing:border-box; cursor:pointer;">
                        <option value="student">Uczeń (Student)</option>
                        <option value="teacher">Nauczyciel (Teacher)</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; font-size:0.8rem; color:#94a3b8; margin-bottom:4px;">Klasa / Poziom (opcjonalne, tylko uczeń)</label>
                    <input type="text" name="class_level" class="form-control" placeholder="np. 4TI" style="background:#0f172a; color:#fff; border:1px solid rgba(255,255,255,0.08); border-radius:8px; width:100%; padding:10px; box-sizing:border-box;">
                </div>
                
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-secondary" style="width:auto; padding:8px 16px; cursor:pointer;" onclick="closeModal('addUserModal')">Anuluj</button>
                    <button type="submit" class="btn btn-primary" style="width:auto; padding:8px 16px; cursor:pointer;">Dodaj</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add School Code Modal -->
    <div class="admin-modal" id="addSchoolCodeModal">
        <div class="admin-modal-card animate__animated animate__zoomIn">
            <div class="admin-modal-header">
                <h3 style="margin:0; color:#fff; font-size:1.15rem;">Dodaj Kod Rejestracji Szkoły</h3>
                <span class="admin-modal-close" onclick="closeModal('addSchoolCodeModal')">✕</span>
            </div>
            <form action="" method="POST">
                <?= SecurityEnterprise::csrfField() ?>
                <input type="hidden" name="action" value="add_school_code">
                
                <div class="form-group" style="margin-bottom:14px;">
                    <label style="display:block; font-size:0.8rem; color:#94a3b8; margin-bottom:4px;">Kod aktywacyjny</label>
                    <input type="text" name="code" placeholder="np. TECH2026" class="form-control" style="background:#0f172a; color:#fff; border:1px solid rgba(255,255,255,0.08); border-radius:8px; width:100%; padding:10px; box-sizing:border-box;" required>
                </div>
                
                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; font-size:0.8rem; color:#94a3b8; margin-bottom:4px;">Nazwa Szkoły / Placówki</label>
                    <input type="text" name="school_name" placeholder="np. Technikum nr 1 w Warszawie" class="form-control" style="background:#0f172a; color:#fff; border:1px solid rgba(255,255,255,0.08); border-radius:8px; width:100%; padding:10px; box-sizing:border-box;" required>
                </div>
                
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-secondary" style="width:auto; padding:8px 16px; cursor:pointer;" onclick="closeModal('addSchoolCodeModal')">Anuluj</button>
                    <button type="submit" class="btn btn-primary" style="width:auto; padding:8px 16px; cursor:pointer;">Dodaj kod</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Price / Access Modal -->
    <div class="admin-modal" id="editPriceModal">
        <div class="admin-modal-card animate__animated animate__zoomIn">
            <div class="admin-modal-header">
                <h3 style="margin:0; color:#fff; font-size:1.15rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" id="editPriceMaterialTitle">Dostęp & Cena</h3>
                <span class="admin-modal-close" onclick="closeModal('editPriceModal')">✕</span>
            </div>
            <form action="" method="POST">
                <?= SecurityEnterprise::csrfField() ?>
                <input type="hidden" name="action" value="update_premium">
                <input type="hidden" name="note_id" id="editPriceTargetId">
                
                <div class="form-group" style="margin-bottom:14px;">
                    <label style="display:block; font-size:0.8rem; color:#94a3b8; margin-bottom:4px;">Typ dostępu</label>
                    <select name="access_type" id="editPriceAccessType" style="background:#0f172a; color:#fff; border:1px solid rgba(255,255,255,0.08); border-radius:8px; width:100%; padding:10px; box-sizing:border-box; cursor:pointer;">
                        <option value="free">Bezpłatny (Free)</option>
                        <option value="premium">Płatny (Premium)</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; font-size:0.8rem; color:#94a3b8; margin-bottom:4px;">Cena Premium (PLN)</label>
                    <input type="number" step="0.01" name="premium_price" id="editPriceValue" class="form-control" style="background:#0f172a; color:#fff; border:1px solid rgba(255,255,255,0.08); border-radius:8px; width:100%; padding:10px; box-sizing:border-box;" required>
                </div>
                
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn btn-secondary" style="width:auto; padding:8px 16px;" onclick="closeModal('editPriceModal')">Anuluj</button>
                    <button type="submit" class="btn btn-primary" style="width:auto; padding:8px 16px;">Zapisz ustawienia</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Chart.js and custom logics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    <script nonce="<?= SecurityEnterprise::getCspNonce() ?>">
        // Advanced Filter for Users
        function applyUsersFilters() {
            const searchVal = document.getElementById('userInputSearch').value.toLowerCase();
            const filterVal = document.getElementById('userRoleFilter').value;
            const rows = document.getElementById('usersTable').getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const usernameCell = rows[i].getElementsByTagName('td')[1];
                const username = usernameCell ? usernameCell.textContent.toLowerCase() : '';
                
                const role = rows[i].getAttribute('data-role');
                const verified = parseInt(rows[i].getAttribute('data-verified')) === 1;
                const creator = parseInt(rows[i].getAttribute('data-creator')) === 1;

                let matchesFilter = true;
                if (filterVal === 'student' && role !== 'student') matchesFilter = false;
                else if (filterVal === 'teacher' && role !== 'teacher') matchesFilter = false;
                else if (filterVal === 'pending_verification' && (parseInt(rows[i].getAttribute('data-verified')) !== 2 || role !== 'teacher')) matchesFilter = false;
                else if (filterVal === 'verified_teachers' && (!verified || role !== 'teacher')) matchesFilter = false;
                else if (filterVal === 'student_creators' && (!creator || role !== 'student')) matchesFilter = false;

                const matchesSearch = username.includes(searchVal);

                if (matchesFilter && matchesSearch) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }

        // Advanced Filter for Materials
        function applyMaterialsFilters() {
            const searchVal = document.getElementById('materialsInputSearch').value.toLowerCase();
            const typeFilter = document.getElementById('materialTypeFilter').value;
            const accessFilter = document.getElementById('materialAccessFilter').value;
            
            const rows = document.getElementById('materialsTable').getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const titleCell = rows[i].getElementsByTagName('td')[1];
                const title = titleCell ? titleCell.textContent.toLowerCase() : '';
                
                const type = rows[i].getAttribute('data-type');
                const access = rows[i].getAttribute('data-access');

                let matchesType = (typeFilter === 'all' || type === typeFilter);
                let matchesAccess = (accessFilter === 'all' || access === accessFilter);
                let matchesSearch = title.includes(searchVal);

                if (matchesType && matchesAccess && matchesSearch) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }

        // Universal Sorting Algorithm
        let sortDirections = {};
        function sortTable(tableId, colIdx, isNum = false) {
            const table = document.getElementById(tableId);
            const tbody = table.querySelector('tbody');
            const rowsArray = Array.from(tbody.querySelectorAll('tr'));
            
            if (sortDirections[tableId + '_' + colIdx] === undefined) {
                sortDirections[tableId + '_' + colIdx] = true;
            } else {
                sortDirections[tableId + '_' + colIdx] = !sortDirections[tableId + '_' + colIdx];
            }
            
            const ascending = sortDirections[tableId + '_' + colIdx];
            
            rowsArray.sort((rowA, rowB) => {
                let cellA = rowA.getElementsByTagName('td')[colIdx].textContent.trim();
                let cellB = rowB.getElementsByTagName('td')[colIdx].textContent.trim();
                
                if (isNum) {
                    let numA = parseFloat(cellA.replace(/[^\d.,-]/g, '').replace(',', '.')) || 0;
                    let numB = parseFloat(cellB.replace(/[^\d.,-]/g, '').replace(',', '.')) || 0;
                    return ascending ? numA - numB : numB - numA;
                } else {
                    return ascending ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
                }
            });
            
            rowsArray.forEach(row => tbody.appendChild(row));
            
            const headers = table.querySelectorAll('th');
            headers.forEach((th, idx) => {
                const icon = th.querySelector('.sort-icon');
                if (icon) {
                    if (idx === colIdx) {
                        icon.textContent = ascending ? '▲' : '▼';
                        icon.style.opacity = '1';
                    } else {
                        icon.textContent = '↕';
                        icon.style.opacity = '0.5';
                    }
                }
            });
        }

        // Modals Management
        function openEditUserModal(id, username, email, classLevel) {
            document.getElementById('editUserTargetId').value = id;
            document.getElementById('editUserUsername').value = username;
            document.getElementById('editUserEmail').value = email;
            document.getElementById('editUserClass').value = classLevel;
            document.getElementById('editUserModal').classList.add('active');
        }

        function openAddUserModal() {
            document.getElementById('addUserModal').classList.add('active');
        }

        function openAddSchoolCodeModal() {
            document.getElementById('addSchoolCodeModal').classList.add('active');
        }

        function openEditPriceModal(id, title, accessType, price) {
            document.getElementById('editPriceTargetId').value = id;
            document.getElementById('editPriceMaterialTitle').textContent = 'Edytuj dostęp: ' + title;
            document.getElementById('editPriceAccessType').value = accessType;
            document.getElementById('editPriceValue').value = price;
            document.getElementById('editPriceModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Charts Loader (Only runs if elements are present on dashboard)
        document.addEventListener('DOMContentLoaded', () => {
            const initCharts = () => {
                if (typeof Chart === 'undefined') {
                    setTimeout(initCharts, 80);
                    return;
                }

                const regCtx = document.getElementById('userRegChart');
                if (regCtx && window.userStatsData) {
                    new Chart(regCtx, {
                        type: 'line',
                        data: {
                            labels: window.userStatsData.labels,
                            datasets: [{
                                label: 'Rejestracje',
                                data: window.userStatsData.values,
                                borderColor: '#818cf8',
                                backgroundColor: 'rgba(129, 140, 248, 0.1)',
                                fill: true,
                                tension: 0.35,
                                borderWidth: 2.5,
                                pointBackgroundColor: '#818cf8'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8e8e93' } },
                                x: { grid: { display: false }, ticks: { color: '#8e8e93' } }
                            }
                        }
                    });
                }

                const revCtx = document.getElementById('revenueTrendChart');
                if (revCtx && window.revStatsData) {
                    new Chart(revCtx, {
                        type: 'bar',
                        data: {
                            labels: window.revStatsData.labels,
                            datasets: [{
                                label: 'Dzienny przychód (PLN)',
                                data: window.revStatsData.values,
                                backgroundColor: '#fbbf24',
                                borderRadius: 6,
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#8e8e93' } },
                                x: { grid: { display: false }, ticks: { color: '#8e8e93' } }
                            }
                        }
                    });
                }
            };
            
            initCharts();
        });
    </script>
</body>
</html>
