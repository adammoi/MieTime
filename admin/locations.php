<?php
if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_admin_or_moderator();

// Handle approve/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $location_id = isset($_POST['location_id']) ? (int)$_POST['location_id'] : 0;

    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // CSRF verification
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!verify_csrf_token($token)) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Token CSRF tidak valid']);
            exit;
        }
        set_flash('danger', 'Token CSRF tidak valid');
        redirect('admin/locations');
    }

    if ($action === 'approve' && $location_id) {
        $ok = db_update('locations', ['status' => 'active'], 'location_id = :location_id', ['location_id' => $location_id]);
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            if ($ok) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Gagal memperbarui status kedai']);
            }
            exit;
        }
        if ($ok) {
            set_flash('success', 'Kedai disetujui');
        } else {
            set_flash('danger', 'Gagal menyetujui kedai');
        }
        redirect('admin/locations');
    }

    if ($action === 'delete' && $location_id) {
        $ok = db_delete('locations', 'location_id = ?', [$location_id]);
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            if ($ok) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Gagal menghapus kedai']);
            }
            exit;
        }
        if ($ok) {
            set_flash('success', 'Kedai dihapus');
        } else {
            set_flash('danger', 'Gagal menghapus kedai');
        }
        redirect('admin/locations');
    }
}


$status = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

$where = '1=1';
if ($status === 'pending' || $status === 'pending_approval') {
    $where = "status = 'pending_approval'";
}

$total = db_count('locations', $where);
$locations = db_fetch_all("SELECT * FROM locations WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?", [$per_page, $offset]);
$csrf_token = generate_csrf_token();

$page_title = 'Admin - Kedai';
include '../includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="row">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="col-12 col-md-10 col-lg-10 px-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">Manajemen Kedai</h3>
                <div>
                    <a href="<?php echo BASE_URL; ?>admin/locations?status=pending_approval" class="btn btn-sm btn-outline-warning">Pending</a>
                    <a href="<?php echo BASE_URL; ?>admin/locations" class="btn btn-sm btn-outline-secondary">Semua</a>
                </div>
            </div>

            <?php if ($total === 0): ?>
                <div class="alert alert-info">Tidak ada kedai.</div>
            <?php else: ?>
                <div class="mx-auto" style="max-width:1100px;">
                    <div class="card shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive px-3">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:60px;">No</th>
                                            <th>Nama</th>
                                            <th>Alamat</th>
                                            <th>Status</th>
                                            <th>Dibuat</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = $offset + 1;
                                        foreach ($locations as $loc): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($loc['name']); ?></td>
                                                <td><?php echo htmlspecialchars($loc['address']); ?></td>
                                                <td><?php echo htmlspecialchars($loc['status']); ?></td>
                                                <td><small><?php echo format_date_id($loc['created_at']); ?></small></td>
                                                <td>
                                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>kedai/<?php echo (int)$loc['location_id']; ?>" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    <button type="button" class="btn btn-sm btn-outline-secondary ms-1" data-bs-toggle="modal" data-bs-target="#editLocationModal" data-location-id="<?php echo (int)$loc['location_id']; ?>" title="Edit kedai">
                                                        <i class="fas fa-pen"></i>
                                                    </button>

                                                    <?php if ($loc['status'] === 'pending_approval'): ?>
                                                        <form method="POST" class="d-inline ajax-approve-location">
                                                            <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="location_id" value="<?php echo (int)$loc['location_id']; ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button class="btn btn-sm btn-success ms-1">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <form method="POST" class="d-inline ms-1 ajax-delete-location" data-location-id="<?php echo (int)$loc['location_id']; ?>">
                                                        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrf_token; ?>">
                                                        <input type="hidden" name="location_id" value="<?php echo (int)$loc['location_id']; ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <button class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                $total_pages = (int)ceil($total / $per_page);
                if ($total_pages > 1):
                ?>
                    <nav class="mt-3">
                        <ul class="pagination">
                            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $p; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- Edit location modal -->
<div class="modal fade" id="editLocationModal" tabindex="-1" aria-labelledby="editLocationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editLocationModalLabel">Edit Kedai</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div id="editLocationAlert" style="display:none;" class="alert" role="alert"></div>
                <form id="editLocationForm">
                    <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="location_id" id="modal_location_id" value="">

                    <div class="mb-3">
                        <label class="form-label">Nama Kedai</label>
                        <input type="text" class="form-control" id="modal_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea class="form-control" id="modal_address" name="address" rows="3" required></textarea>
                    </div>

                    <!-- search box for geocoding -->
                    <div class="mb-3">
                        <label class="form-label">Cari Lokasi</label>
                        <div class="input-group">
                            <input type="text" id="modal_search_input" class="form-control" placeholder="Ketik alamat atau nama tempat...">
                            <button class="btn btn-outline-secondary" type="button" id="modal_search_btn">Cari</button>
                        </div>
                        <div id="modal_search_results" class="list-group mt-2" style="max-height:180px; overflow:auto; display:none;"></div>
                    </div>

                    <!-- hidden lat/lng (kept for form submission) and visible read-only coords -->
                    <input type="hidden" id="modal_latitude" name="latitude">
                    <input type="hidden" id="modal_longitude" name="longitude">
                    <div class="mb-2"><small id="modal_coords_display" class="text-muted">Lat: -, Lng: -</small></div>

                    <!-- Status is managed elsewhere; removed from modal -->
                </form>
                <div class="mb-3">
                    <label class="form-label">Lokasi di Peta</label>
                    <div id="modal_map" style="height:300px; border-radius:8px; overflow:hidden;"></div>
                    <small class="text-muted">Geser pin untuk memperbarui latitude/longitude.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="saveLocationBtn">Simpan perubahan</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var editModal = document.getElementById('editLocationModal');
        if (!editModal) return;

        editModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var locId = button.getAttribute('data-location-id');
            // initialize map lazily when modal is shown
            setTimeout(function() {
                initModalMap();
                loadLocation(locId);
            }, 50);
        });

        // Leaflet map instance for modal
        var modalMap = null;
        var modalMarker = null;

        function initModalMap() {
            if (modalMap) return;
            if (typeof L === 'undefined') {
                console.warn('Leaflet not loaded');
                return;
            }

            modalMap = L.map('modal_map', {
                scrollWheelZoom: false,
                doubleClickZoom: true
            });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(modalMap);

            // default view
            modalMap.setView([-6.200000, 106.816666], 13);

            // if modal shown, invalidate size so map renders correctly
            modalMap.whenReady(function() {
                setTimeout(function() {
                    modalMap.invalidateSize();
                }, 200);
            });

            // enable scroll wheel zoom only while mouse is over the map (prevents accidental page scroll)
            var mapContainer = document.getElementById('modal_map');
            if (mapContainer) {
                mapContainer.addEventListener('mouseenter', function() {
                    if (modalMap && modalMap.scrollWheelZoom) modalMap.scrollWheelZoom.enable();
                });
                mapContainer.addEventListener('mouseleave', function() {
                    if (modalMap && modalMap.scrollWheelZoom) modalMap.scrollWheelZoom.disable();
                });
            }

            // allow placing marker by clicking on the map
            modalMap.on('click', function(e) {
                var p = e.latlng;
                setMarkerLatLng(p.lat, p.lng);
            });
        }

        function updateCoordsDisplay(lat, lng) {
            var el = document.getElementById('modal_coords_display');
            if (!el) return;
            el.textContent = 'Lat: ' + (parseFloat(lat) || 0).toFixed(6) + ', Lng: ' + (parseFloat(lng) || 0).toFixed(6);
        }

        function setMarkerLatLng(lat, lng) {
            if (!modalMap) return;
            lat = parseFloat(lat) || 0;
            lng = parseFloat(lng) || 0;
            if (modalMarker) {
                modalMarker.setLatLng([lat, lng]);
            } else {
                modalMarker = L.marker([lat, lng], {
                    draggable: true
                }).addTo(modalMap);
                modalMarker.on('dragend', function(e) {
                    var p = e.target.getLatLng();
                    document.getElementById('modal_latitude').value = p.lat.toFixed(6);
                    document.getElementById('modal_longitude').value = p.lng.toFixed(6);
                    updateCoordsDisplay(p.lat, p.lng);
                });
            }
            document.getElementById('modal_latitude').value = lat.toFixed(6);
            document.getElementById('modal_longitude').value = lng.toFixed(6);
            updateCoordsDisplay(lat, lng);
            modalMap.setView([lat, lng], 15);
        }

        function showAlert(message, type) {
            var alertEl = document.getElementById('editLocationAlert');
            if (!alertEl) return;
            alertEl.className = 'alert alert-' + (type || 'info');
            alertEl.textContent = message;
            alertEl.style.display = 'block';
        }

        function clearAlert() {
            var alertEl = document.getElementById('editLocationAlert');
            if (!alertEl) return;
            alertEl.style.display = 'none';
            alertEl.textContent = '';
        }

        function loadLocation(locationId) {
            clearAlert();
            var form = document.getElementById('editLocationForm');
            if (!form) return;
            document.getElementById('modal_location_id').value = locationId;
            fetch('<?php echo BASE_URL; ?>admin/location_api.php?location_id=' + encodeURIComponent(locationId), {
                    credentials: 'same-origin'
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(json) {
                    if (json.error) {
                        showAlert(json.error, 'danger');
                        return;
                    }
                    var l = json.location;
                    document.getElementById('modal_name').value = l.name || '';
                    document.getElementById('modal_address').value = l.address || '';
                    // update hidden lat/lng and set marker on map
                    var lat = l.latitude ? parseFloat(l.latitude) : -6.200000;
                    var lng = l.longitude ? parseFloat(l.longitude) : 106.816666;
                    if (modalMap) {
                        setMarkerLatLng(lat, lng);
                        setTimeout(function() {
                            modalMap.invalidateSize();
                        }, 200);
                    } else {
                        // still set hidden inputs if map not ready
                        document.getElementById('modal_latitude').value = lat.toFixed(6);
                        document.getElementById('modal_longitude').value = lng.toFixed(6);
                        updateCoordsDisplay(lat, lng);
                    }
                })
                .catch(function(err) {
                    showAlert('Gagal memuat data kedai', 'danger');
                    console.error(err);
                });
        }

        // small toast helper
        function showToast(message, type) {
            type = type || 'success';
            var el = document.createElement('div');
            el.className = 'alert alert-' + type + ' shadow-sm';
            el.style.position = 'fixed';
            el.style.top = '1rem';
            el.style.right = '1rem';
            el.style.zIndex = 1080;
            el.textContent = message;
            document.body.appendChild(el);
            setTimeout(function() {
                el.style.opacity = '0';
                setTimeout(function() {
                    el.remove();
                }, 300);
            }, 2500);
        }

        // AJAX delete for locations
        function bindAjaxDeleteLocations() {
            var forms = document.querySelectorAll('form.ajax-delete-location');
            forms.forEach(function(form) {
                if (form.__bound) return;
                form.__bound = true;
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var locationId = form.getAttribute('data-location-id') || form.querySelector('input[name="location_id"]').value;
                    if (!confirm('Hapus kedai ini?')) return;
                    var data = new FormData(form);
                    fetch((form.getAttribute('action') || window.location.href), {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: data
                        })
                        .then(function(res) {
                            return res.json();
                        })
                        .then(function(json) {
                            if (json.error) {
                                showToast(json.error, 'danger');
                                return;
                            }
                            var row = form.closest('tr');
                            if (row) row.remove();
                            showToast('Kedai dihapus', 'success');
                        })
                        .catch(function(err) {
                            console.error(err);
                            showToast('Gagal menghapus kedai', 'danger');
                        });
                });
            });
        }

        // AJAX approve for locations
        function bindAjaxApproveLocations() {
            var forms = document.querySelectorAll('form.ajax-approve-location');
            forms.forEach(function(form) {
                if (form.__boundApprove) return;
                form.__boundApprove = true;
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var data = new FormData(form);
                    fetch((form.getAttribute('action') || window.location.href), {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: data
                        })
                        .then(function(res) {
                            return res.json();
                        })
                        .then(function(json) {
                            if (json.error) {
                                showToast(json.error, 'danger');
                                return;
                            }
                            // on success, reload to show updated status (could replace row instead)
                            showToast('Kedai disetujui', 'success');
                            setTimeout(function() {
                                location.reload();
                            }, 700);
                        })
                        .catch(function(err) {
                            console.error(err);
                            showToast('Gagal menyetujui kedai', 'danger');
                        });
                });
            });
        }

        // Save button behavior: disable + spinner while saving
        var saveLocationBtn = document.getElementById('saveLocationBtn');
        if (saveLocationBtn) {
            saveLocationBtn.addEventListener('click', function() {
                var form = document.getElementById('editLocationForm');
                if (!form) return;
                clearAlert();
                var data = new FormData(form);
                // disable
                saveLocationBtn.disabled = true;
                var prevHtml = saveLocationBtn.innerHTML;
                saveLocationBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...';

                fetch('<?php echo BASE_URL; ?>admin/location_api.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: data
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(json) {
                        if (json.error) {
                            showAlert(json.error, 'danger');
                            saveLocationBtn.disabled = false;
                            saveLocationBtn.innerHTML = prevHtml;
                            return;
                        }
                        var modalEl = document.getElementById('editLocationModal');
                        var bsModal = bootstrap.Modal.getInstance(modalEl);
                        if (bsModal) bsModal.hide();
                        showToast('Perubahan tersimpan', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 700);
                    })
                    .catch(function(err) {
                        console.error(err);
                        showAlert('Gagal menyimpan perubahan', 'danger');
                        saveLocationBtn.disabled = false;
                        saveLocationBtn.innerHTML = prevHtml;
                    });
            });
        }

        // bind ajax forms
        bindAjaxDeleteLocations();
        bindAjaxApproveLocations();

        // --- Nominatim search for modal map ---
        var searchInput = document.getElementById('modal_search_input');
        var searchBtn = document.getElementById('modal_search_btn');
        var searchResults = document.getElementById('modal_search_results');

        function doLocationSearch(q) {
            if (!q || q.trim() === '') {
                searchResults.style.display = 'none';
                return;
            }
            searchResults.innerHTML = '<div class="list-group-item">Mencari...</div>';
            searchResults.style.display = 'block';
            fetch('https://nominatim.openstreetmap.org/search?format=json&limit=5&q=' + encodeURIComponent(q), {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(list) {
                    if (!Array.isArray(list) || list.length === 0) {
                        searchResults.innerHTML = '<div class="list-group-item">Tidak ditemukan</div>';
                        return;
                    }
                    searchResults.innerHTML = '';
                    list.forEach(function(item) {
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'list-group-item list-group-item-action';
                        btn.setAttribute('data-lat', item.lat);
                        btn.setAttribute('data-lon', item.lon);
                        btn.textContent = item.display_name;
                        searchResults.appendChild(btn);
                    });
                })
                .catch(function(err) {
                    console.error(err);
                    searchResults.innerHTML = '<div class="list-group-item text-danger">Terjadi kesalahan</div>';
                });
        }

        if (searchBtn && searchInput && searchResults) {
            searchBtn.addEventListener('click', function() {
                doLocationSearch(searchInput.value);
            });
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    doLocationSearch(searchInput.value);
                }
            });
            // delegate clicks on results
            searchResults.addEventListener('click', function(e) {
                var btn = e.target.closest('.list-group-item');
                if (!btn) return;
                var lat = btn.getAttribute('data-lat');
                var lon = btn.getAttribute('data-lon');
                if (lat && lon) {
                    setMarkerLatLng(lat, lon);
                    searchResults.style.display = 'none';
                }
            });
            // hide results when modal closed
            editModal.addEventListener('hidden.bs.modal', function() {
                searchResults.style.display = 'none';
            });
        }
    });
</script>