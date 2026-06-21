<!-- Tab 1: Users -->
<section id="usersSection" class="admin-content-section">
    <div class="table-controls">
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <input type="text" id="userInputSearch" class="search-input" placeholder="Szukaj użytkownika..." onkeyup="applyUsersFilters()">
            
            <select id="userRoleFilter" class="filter-select" onchange="applyUsersFilters()">
                <option value="all">Wszystkie Role</option>
                <option value="student">Tylko Uczniowie</option>
                <option value="teacher">Tylko Nauczyciele</option>
                <option value="pending_verification">Oczekujący na Weryfikację</option>
                <option value="verified_teachers">Zweryfikowani Nauczyciele</option>
                <option value="student_creators">Twórcy Studenccy</option>
            </select>
        </div>
        <button type="button" class="btn-add" onclick="openAddUserModal()">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
            Dodaj Użytkownika
        </button>
    </div>
    <div class="saas-table-wrapper" style="background: #121212; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; overflow-x: auto;">
        <table class="saas-table" id="usersTable" style="width: 100%; border-collapse: collapse; min-width: 900px;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.08); text-align: left;">
                    <th class="sortable-header" onclick="sortTable('usersTable', 0, true)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">ID <span class="sort-icon">↕</span></th>
                    <th class="sortable-header" onclick="sortTable('usersTable', 1, false)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Użytkownik <span class="sort-icon">↕</span></th>
                    <th class="sortable-header" onclick="sortTable('usersTable', 2, false)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">E-mail <span class="sort-icon">↕</span></th>
                    <th style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Typ (Zmień)</th>
                    <th style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Weryfikacja</th>
                    <th style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Twórca Stud.</th>
                    <th class="sortable-header" onclick="sortTable('usersTable', 6, false)" style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Klasa <span class="sort-icon">↕</span></th>
                    <th style="padding: 12px 16px; color: #8e8e93; font-size: 0.75rem; text-transform: uppercase;">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);" 
                        data-role="<?= htmlspecialchars($u['type']) ?>" 
                        data-verified="<?= (int)($u['is_verified'] ?? 0) ?>" 
                        data-creator="<?= (int)($u['is_student_creator'] ?? 0) ?>">
                        <td style="padding: 14px 16px;"><?= $u['id'] ?></td>
                        <td style="padding: 14px 16px;"><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                        <td style="padding: 14px 16px;"><?= htmlspecialchars($u['email']) ?></td>
                        <td style="padding: 14px 16px;">
                            <?php if ($u['id'] === 999999): ?>
                                <span class="badge-admin admin">ADMIN</span>
                            <?php else: ?>
                                <form action="" method="POST" class="inline-form">
                                    <?= SecurityEnterprise::csrfField() ?>
                                    <input type="hidden" name="action" value="change_user_type">
                                    <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                    <select name="new_type" onchange="this.form.submit()" style="background:#1c1c1e; color:#fff; border:1px solid rgba(255,255,255,0.1); padding:4px; border-radius:4px; font-size:0.8rem; cursor:pointer;">
                                        <option value="student" <?= $u['type'] === 'student' ? 'selected' : '' ?>>Uczeń</option>
                                        <option value="teacher" <?= $u['type'] === 'teacher' ? 'selected' : '' ?>>Nauczyciel</option>
                                    </select>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 14px 16px;">
                            <?php if ($u['type'] === 'teacher'): ?>
                                <?php if ($u['is_verified'] == 2): ?>
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        <span class="badge-admin pending-verification pulse-amber">Oczekuje weryfikacji</span>
                                        <?php if(!empty($u['verification_document'])): ?>
                                            <a href="admin_users.php?view_doc=<?= $u['id'] ?>" target="_blank" style="color: #818cf8; font-size: 0.8rem; font-weight: 600; text-decoration: none;">Zobacz dokument ↗</a>
                                        <?php endif; ?>
                                        <div style="display: flex; gap: 4px; margin-top: 4px;">
                                            <form action="" method="POST" style="margin: 0;">
                                                <?= SecurityEnterprise::csrfField() ?>
                                                <input type="hidden" name="action" value="approve_teacher">
                                                <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" style="padding: 2px 6px; font-size: 0.7rem; border-radius: 4px; border: none; background: #10b981; color: white; cursor: pointer; font-weight: 600;">Zatwierdź</button>
                                            </form>
                                            <form action="" method="POST" style="margin: 0;">
                                                <?= SecurityEnterprise::csrfField() ?>
                                                <input type="hidden" name="action" value="reject_teacher">
                                                <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" style="padding: 2px 6px; font-size: 0.7rem; border-radius: 4px; border: none; background: #ef4444; color: white; cursor: pointer; font-weight: 600;">Odrzuć</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <form action="" method="POST" class="inline-form">
                                        <?= SecurityEnterprise::csrfField() ?>
                                        <input type="hidden" name="action" value="toggle_verification">
                                        <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="verify" value="<?= $u['is_verified'] ? '0' : '1' ?>">
                                        <button type="submit" class="badge-admin <?= $u['is_verified'] ? 'verified' : 'unverified' ?>" style="border:none; cursor:pointer;">
                                            <?= $u['is_verified'] ? 'Zweryfikowany' : 'Niezweryfikowany' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:#8e8e93; font-size:0.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 14px 16px;">
                            <?php if ($u['type'] === 'student'): ?>
                                <form action="" method="POST" class="inline-form">
                                    <?= SecurityEnterprise::csrfField() ?>
                                    <input type="hidden" name="action" value="toggle_student_creator">
                                    <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="creator" value="<?= $u['is_student_creator'] ? '0' : '1' ?>">
                                    <button type="submit" class="badge-admin <?= $u['is_student_creator'] ? 'verified' : 'unverified' ?>" style="border:none; cursor:pointer;">
                                        <?= $u['is_student_creator'] ? 'Tak (Twórca)' : 'Nie' ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color:#8e8e93; font-size:0.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 14px 16px;"><?= htmlspecialchars($u['class_level'] ?: 'Brak') ?></td>
                        <td style="padding: 14px 16px;">
                            <?php if ($u['id'] !== 999999): ?>
                                <button class="btn btn-secondary" style="padding:4px 8px; font-size:0.75rem; width:auto; border-radius:4px; cursor:pointer; margin-right:4px;" 
                                        onclick="openEditUserModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>', '<?= htmlspecialchars($u['class_level'] ?: '', ENT_QUOTES) ?>')">
                                    Edytuj
                                </button>
                                
                                <form action="" method="POST" class="inline-form" onsubmit="return confirm('Czy na pewno chcesz trwale usunąć tego użytkownika?')">
                                    <?= SecurityEnterprise::csrfField() ?>
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-danger" style="padding:4px 8px; font-size:0.75rem; width:auto; border:none; border-radius:4px; cursor:pointer;">Usuń</button>
                                </form>
                            <?php else: ?>
                                <span style="color:#8e8e93; font-size:0.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
