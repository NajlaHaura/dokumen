<?php
session_start();
include "db.php"; // koneksi database
$db = new DB();
$conn = $db->koneksiDB();

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'save_document_title':
                $judul = trim($_POST['judul']);
                
                if (empty($judul)) {
                    echo json_encode(['success' => false, 'error' => 'Judul tidak boleh kosong']);
                    exit();
                }
                
                // Check if user already has a document title
                $check_query = "SELECT id FROM judul_dokumen WHERE user_id = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param("i", $user_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    // Update existing title
                    $update_query = "UPDATE judul_dokumen SET judul = ? WHERE user_id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("si", $judul, $user_id);
                    
                    if ($update_stmt->execute()) {
                        echo json_encode(['success' => true, 'message' => 'Judul dokumen berhasil diupdate']);
                    } else {
                        echo json_encode(['success' => false, 'error' => $conn->error]);
                    }
                } else {
                    // Insert new title
                    $insert_query = "INSERT INTO judul_dokumen (user_id, judul) VALUES (?, ?)";
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bind_param("is", $user_id, $judul);
                    
                    if ($insert_stmt->execute()) {
                        $judul_id = $conn->insert_id;
                        echo json_encode(['success' => true, 'id' => $judul_id, 'message' => 'Judul dokumen berhasil disimpan']);
                    } else {
                        echo json_encode(['success' => false, 'error' => $conn->error]);
                    }
                }
                exit();
                
            case 'get_document_title':
                $query = "SELECT judul FROM judul_dokumen WHERE user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    echo json_encode(['success' => true, 'judul' => $row['judul']]);
                } else {
                    echo json_encode(['success' => true, 'judul' => '']);
                }
                exit();
            
            case 'delete_document_title':
                // Perbaikan: Hapus data laporan terkait terlebih dahulu
                $check_query = "SELECT id FROM judul_dokumen WHERE user_id = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param("i", $user_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    $judul_row = $check_result->fetch_assoc();
                    $id_judul_dokumen = $judul_row['id'];
                    
                    // Hapus semua item laporan yang terkait
                    $delete_laporan_query = "DELETE FROM laporan WHERE id_judul_dokumen = ? AND user_id = ?";
                    $delete_laporan_stmt = $conn->prepare($delete_laporan_query);
                    $delete_laporan_stmt->bind_param("ii", $id_judul_dokumen, $user_id);
                    $delete_laporan_stmt->execute();

                    // Setelah item laporan dihapus, baru hapus judulnya
                    $delete_judul_query = "DELETE FROM judul_dokumen WHERE id = ? AND user_id = ?";
                    $delete_judul_stmt = $conn->prepare($delete_judul_query);
                    $delete_judul_stmt->bind_param("ii", $id_judul_dokumen, $user_id);
                    
                    if ($delete_judul_stmt->execute()) {
                        echo json_encode(['success' => true, 'message' => 'Judul dokumen dan semua laporan terkait berhasil dihapus']);
                    } else {
                        echo json_encode(['success' => false, 'error' => $conn->error]);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'Tidak ada judul dokumen untuk dihapus.']);
                }
                exit();

            case 'save_item':
                $type = $_POST['type'];
                $judul = $_POST['judul'];
                $parent_id = isset($_POST['parent_id']) ? $_POST['parent_id'] : null;
                
                $judul_query = "SELECT id FROM judul_dokumen WHERE user_id = ?";
                $judul_stmt = $conn->prepare($judul_query);
                $judul_stmt->bind_param("i", $user_id);
                $judul_stmt->execute();
                $judul_result = $judul_stmt->get_result();
                $id_judul_dokumen = null;
                
                if ($judul_row = $judul_result->fetch_assoc()) {
                    $id_judul_dokumen = $judul_row['id'];
                }
                
                $query = "INSERT INTO laporan (user_id, type, judul, parent_id, id_judul_dokumen) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("issii", $user_id, $type, $judul, $parent_id, $id_judul_dokumen);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'id' => $conn->insert_id]);
                } else {
                    echo json_encode(['success' => false, 'error' => $conn->error]);
                }
                exit();
                
            case 'update_item':
                $id = $_POST['id'];
                $judul = $_POST['judul'];
                
                $query = "UPDATE laporan SET judul = ? WHERE id = ? AND user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sii", $judul, $id, $user_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => $conn->error]);
                }
                exit();
            
            case 'delete_item':
                $id = $_POST['id'];
                
                // Delete item and all its children (cascade delete)
                $deleteChildren = function($parent_id) use ($conn, $user_id, &$deleteChildren) {
                    $query = "SELECT id FROM laporan WHERE parent_id = ? AND user_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ii", $parent_id, $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        $deleteChildren($row['id']);
                    }
                    
                    $query = "DELETE FROM laporan WHERE parent_id = ? AND user_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ii", $parent_id, $user_id);
                    $stmt->execute();
                };
                
                // Delete children first
                $deleteChildren($id);
                
                // Delete the item itself
                $query = "DELETE FROM laporan WHERE id = ? AND user_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $id, $user_id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => $conn->error]);
                }
                exit();
                
            case 'load_items':
                $query = "SELECT * FROM laporan WHERE user_id = ? ORDER BY type ASC";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $items = [];
                while ($row = $result->fetch_assoc()) {
                    $items[] = $row;
                }
                
                echo json_encode(['success' => true, 'items' => $items]);
                exit();
        }
    } catch (Exception $e) {
        // Tangkap error dan kembalikan respons JSON yang valid
        echo json_encode(['success' => false, 'error' => 'Server Error: ' . $e->getMessage()]);
    }
    exit();
}

// Get document title for header
$title_query = "SELECT judul FROM judul_dokumen WHERE user_id = ?";
$title_stmt = $conn->prepare($title_query);
$title_stmt->bind_param("i", $user_id);
$title_stmt->execute();
$title_result = $title_stmt->get_result();

$document_title = "";
$has_saved_title = false;
if ($title_row = $title_result->fetch_assoc()) {
    $document_title = $title_row['judul'];
    $has_saved_title = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan UI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom styles for the app */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fefefe;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            width: 90%;
            max-width: 400px;
            text-align: center;
            animation: fadeIn 0.3s;
        }
        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-top: 20px;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        
        /* Divider style */
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 16px 0;
            width: 100%;
        }
        
        .laporan-item {
            width: 100%;
        }
        
        /* Loading indicator */
        .loading {
            opacity: 0.5;
            pointer-events: none;
        }

        /* Title input styles */
        .title-input-container {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .title-display {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body class="bg-gray-100 flex justify-center items-center min-h-screen p-5">
    <div class="container w-full max-w-4xl bg-white rounded-xl shadow-lg p-8">
        <div class="header flex justify-between items-center mb-5 border-b pb-4 border-gray-200">
            
            <div class="title-section flex-1">
                <div id="title-input-form" class="title-input-container" style="<?= $has_saved_title ? 'display: none;' : '' ?>">
                    <h1 class="text-2xl font-semibold text-gray-800 m-0">PROPOSAL</h1>
                    <input type="text" id="document-title-input" 
                            class="flex-1 p-2 border border-gray-300 rounded-lg text-lg font-medium text-gray-700 focus:outline-none focus:border-yellow-600 focus:ring-2 focus:ring-yellow-200" 
                            placeholder="Masukkan judul proposal di sini"
                            value="<?= htmlspecialchars($document_title) ?>">
                    <button id="save-title-btn" class="bg-transparent border-none text-green-500 cursor-pointer text-xl transition-colors duration-200 hover:text-green-700" title="Simpan Judul">&#x2714;</button>
                </div>

                <div id="title-display" class="title-display" style="<?= $has_saved_title ? '' : 'display: none;' ?>">
                    <h1 class="text-2xl font-semibold text-gray-800 m-0">
                        Proposal <span id="display-title"><?= htmlspecialchars($document_title) ?></span>
                    </h1>
                    <button id="edit-title-btn" class="bg-transparent border-none text-blue-500 cursor-pointer text-xl transition-colors duration-200 hover:text-blue-700" title="Edit Judul">&#x270E;</button>
                    <button id="delete-title-btn" class="bg-transparent border-none text-red-500 cursor-pointer text-xl transition-colors duration-200 hover:text-red-700" title="Hapus Judul">&#x2326;</button>
                </div>
            </div>
        </div>
        
        <div id="laporan-container">
            </div>

        <div class="bottom-buttons flex flex-col items-center gap-4 mt-6">
            <div class="add-buttons-container flex gap-4 w-full justify-center">
                <button class="add-bab-btn px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-gray-700 text-white hover:bg-gray-800" id="add-bab-btn">Tambahkan BAB Baru</button>
                <button class="add-sub-btn px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-gray-700 text-white hover:bg-gray-800" id="add-sub-btn">Tambahkan sub-BAB</button>
                <button class="add-point-btn px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-gray-700 text-white hover:bg-gray-800" id="add-point-btn">Tambahkan Poin</button>
            </div>

            <div class="flex items-center gap-4">
                <button class="save-btn px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-yellow-600 text-white hover:bg-yellow-700" id="save-laporan-btn">Save</button>
                <button class="download-btn px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-yellow-600 text-white hover:bg-yellow-700" id="download-laporan-btn">Download Laporan</button>
            </div>

            <div id="download-options-container" class="download-options hidden flex-col gap-2 w-full max-w-[250px]">
                <button class="px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-yellow-600 text-white hover:bg-yellow-700" id="download-doc-btn">DOC</button>
                <button class="px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-yellow-600 text-white hover:bg-yellow-700" id="download-pdf-btn">PDF</button>
            </div>
        </div>
    </div>
    
    <div id="custom-modal" class="modal">
        <div class="modal-content">
            <p id="modal-message"></p>
            <div class="modal-buttons">
                <button id="modal-ok-btn" class="px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-gray-700 text-white hover:bg-gray-800">OK</button>
                <button id="modal-cancel-btn" class="px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-gray-500 text-white hover:bg-gray-600 hidden">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Helper function for custom modal
        const customModal = (message, isConfirm = false) => {
            return new Promise(resolve => {
                const modal = document.getElementById('custom-modal');
                const modalMessage = document.getElementById('modal-message');
                const modalOkBtn = document.getElementById('modal-ok-btn');
                const modalCancelBtn = document.getElementById('modal-cancel-btn');

                modalMessage.textContent = message;
                modalCancelBtn.style.display = isConfirm ? 'block' : 'none';
                modal.style.display = 'flex';

                modalOkBtn.onclick = () => {
                    modal.style.display = 'none';
                    resolve(true);
                };

                modalCancelBtn.onclick = () => {
                    modal.style.display = 'none';
                    resolve(false);
                };
            });
        };

        // AJAX helper function
        const sendAjaxRequest = async (data) => {
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                });

                // Handle non-JSON responses gracefully
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return await response.json();
                } else {
                    console.error("Server did not return a valid JSON response.");
                    const text = await response.text();
                    console.log("Server response:", text);
                    return { success: false, error: 'Respons dari server tidak valid. Silakan coba lagi.' };
                }
            } catch (error) {
                console.error('AJAX Error:', error);
                return { success: false, error: 'Terjadi kesalahan jaringan. Mohon periksa koneksi Anda dan coba lagi.' };
            }
        };

        // Document title management functions
        const saveDocumentTitle = async () => {
            const titleInput = document.getElementById('document-title-input');
            const title = titleInput.value.trim();

            if (!title) {
                await customModal('Judul dokumen tidak boleh kosong.');
                return;
            }

            const response = await sendAjaxRequest({
                action: 'save_document_title',
                judul: title
            });

            if (response.success) {
                // Hide input form, show display
                document.getElementById('title-input-form').style.display = 'none';
                document.getElementById('title-display').style.display = 'flex';
                document.getElementById('display-title').textContent = title;

                await customModal('Judul dokumen berhasil disimpan!');
            } else {
                await customModal('Gagal menyimpan judul dokumen: ' + response.error);
            }
        };

        const editDocumentTitle = () => {
            const currentTitle = document.getElementById('display-title').textContent;
            document.getElementById('document-title-input').value = currentTitle;

            // Show input form, hide display
            document.getElementById('title-display').style.display = 'none';
            document.getElementById('title-input-form').style.display = 'flex';
        };

        const deleteDocumentTitle = async () => {
            const confirmed = await customModal('Apakah Anda yakin ingin menghapus judul dokumen ini?', true);
            if (confirmed) {
                const response = await sendAjaxRequest({
                    action: 'delete_document_title'
                });

                if (response.success) {
                    // Sembunyikan display, tampilkan input form
                    document.getElementById('title-display').style.display = 'none';
                    document.getElementById('title-input-form').style.display = 'flex';
                    document.getElementById('document-title-input').value = ''; // Kosongkan input
                    
                    // Tambahan: muat ulang item laporan jika diperlukan
                    // loadItemsFromDB(); 

                    await customModal('Judul dokumen berhasil dihapus!');
                } else {
                    await customModal('Gagal menghapus judul dokumen: ' + response.error);
                }
            }
        };

        let counters = {
            bab: 1
        };

        // Generate unique ID for each item
        let itemId = 1;
        let dbItems = []; // Store database items

        // Function to determine item type based on hierarchy path
        const getItemTypeFromPath = (hierarchyPath) => {
            const parts = hierarchyPath.split('.');
            if (parts.length === 1) {
                return 'bab';
            } else if (parts.length === 2) {
                return 'sub-bab';
            } else {
                return 'point';
            }
        };

        const createLaporanItem = (type, hierarchyPath, dbId = null, savedTitle = '') => {
            const newDiv = document.createElement('div');
            let classList = ['laporan-item', 'flex', 'flex-wrap', 'items-center', 'gap-4', 'mb-4'];
            let labelText = '';
            let placeholderText = '';
            
            // Add unique ID
            newDiv.dataset.itemId = itemId++;
            newDiv.dataset.type = type;
            newDiv.dataset.hierarchyPath = hierarchyPath;
            if (dbId) newDiv.dataset.dbId = dbId;
            
            // Calculate padding based on hierarchy depth
            const level = hierarchyPath.split('.').length - 1;
            const paddingLeft = level * 20;
            newDiv.style.paddingLeft = paddingLeft + 'px';

            if (type === 'bab') {
                labelText = `BAB ${hierarchyPath}`;
                placeholderText = 'Masukkan judul BAB di sini';
            } else if (type === 'sub-bab') {
                labelText = hierarchyPath;
                placeholderText = 'Masukkan judul sub-BAB di sini';
            } else if (type === 'point') {
                labelText = hierarchyPath;
                placeholderText = 'Masukkan judul poin di sini';
            } else if (type === 'prompt') {
                labelText = ''; // Prompt has no label
                placeholderText = `Isi prompt ${hierarchyPath} [...]`;
                
                // Add divider before prompt
                const divider = document.createElement('div');
                divider.className = 'divider';
                divider.style.paddingLeft = paddingLeft + 'px';
                document.getElementById('laporan-container').appendChild(divider);
            }
            
            newDiv.className = classList.join(' ');

            let innerHTML = `
                ${labelText ? `<div class="item-label font-semibold whitespace-nowrap">${labelText}</div>` : ''}
                <textarea class="${type === 'prompt' ? '' : 'input-field'} flex-grow p-3 border border-gray-300 rounded-lg text-sm text-gray-700 bg-gray-50 resize-y min-h-[40px] focus:outline-none focus:border-yellow-600 focus:ring-2 focus:ring-yellow-200" placeholder="${placeholderText}">${savedTitle}</textarea>
                <div class="saved-text flex-grow p-3 font-medium text-gray-800 ${savedTitle ? '' : 'hidden'}">${savedTitle ? (labelText ? `${labelText} (${savedTitle})` : savedTitle) : ''}</div>
            `;

            if (type !== 'prompt') {
                innerHTML += `
                    <div class="icons flex gap-2">
                        <button class="icon-btn save-btn bg-transparent border-none text-green-500 cursor-pointer text-xl transition-colors duration-200 hover:text-green-700 ${savedTitle ? 'hidden' : ''}" title="Simpan">&#x2714;</button>
                        <button class="icon-btn edit-btn bg-transparent border-none text-blue-500 cursor-pointer text-xl transition-colors duration-200 hover:text-blue-700 ${savedTitle ? '' : 'hidden'}" title="Edit">&#x270E;</button>
                        ${type === 'point' ? '<button class="icon-btn add-child-btn bg-transparent border-none text-blue-500 cursor-pointer text-xl transition-colors duration-200 hover:text-blue-700" title="Tambah Child">&#x2B;</button>' : ''}
                        <button class="icon-btn delete-icon-btn bg-transparent border-none text-red-500 cursor-pointer text-xl transition-colors duration-200 hover:text-red-700" title="Hapus">&#x2326;</button>
                    </div>
                `;
            } else {
                innerHTML += `
                    <div class="buttons flex flex-col gap-3" style="align-self: flex-start; margin-top: 0;">
                        <button class="send-btn px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-gray-700 text-white hover:bg-gray-800">Send</button>
                        <button class="delete-btn px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-gray-500 text-white hover:bg-gray-600">Delete</button>
                    </div>
                `;
                innerHTML += `<div class="ai-item-result-box bg-gray-100 border border-gray-200 rounded-lg min-h-[100px] p-4 mt-4 flex justify-center items-center text-gray-400 italic whitespace-pre-wrap w-full"><span>Hasil dari prompt AI</span></div>`;
            }

            newDiv.innerHTML = innerHTML;
            
            // Hide textarea if saved
            if (savedTitle && type !== 'prompt') {
                newDiv.querySelector('textarea').style.display = 'none';
                newDiv.querySelector('.item-label').style.display = 'none';
            }
            
            return newDiv;
        };

        // Function to load items from database
        const loadItemsFromDB = async () => {
            const response = await sendAjaxRequest({ action: 'load_items' });
            if (response.success) {
                dbItems = response.items;
                renderItemsFromDB();
            } else {
                await customModal('Tidak dapat memuat data. Mohon muat ulang halaman atau coba lagi.');
            }
        };

        // Function to sort items by hierarchy path
        const sortByHierarchy = (items) => {
            return items.sort((a, b) => {
                const pathA = a.type.split('.').map(num => parseInt(num));
                const pathB = b.type.split('.').map(num => parseInt(num));
                
                const maxLength = Math.max(pathA.length, pathB.length);
                
                for (let i = 0; i < maxLength; i++) {
                    const numA = pathA[i] || 0;
                    const numB = pathB[i] || 0;
                    
                    if (numA !== numB) {
                        return numA - numB;
                    }
                }
                
                return 0;
            });
        };

        // Function to render items from database
        const renderItemsFromDB = () => {
            const container = document.getElementById('laporan-container');
            container.innerHTML = '';
            
            if (dbItems.length === 0) return;
            
            // Sort items by hierarchy
            const sortedItems = sortByHierarchy(dbItems);
            
            sortedItems.forEach(item => {
                const hierarchyPath = item.type; // type sekarang berisi nomor hierarki
                const itemType = getItemTypeFromPath(hierarchyPath);
                
                const itemElement = createLaporanItem(itemType, hierarchyPath, item.id, item.judul);
                const promptElement = createLaporanItem('prompt', hierarchyPath);
                
                container.appendChild(itemElement);
                container.appendChild(promptElement);
            });
        };

        // Function to get all items in proper hierarchical order
        const getAllItems = () => {
            const container = document.getElementById('laporan-container');
            return Array.from(container.children).filter(item => 
                item.classList.contains('laporan-item') && item.dataset.hierarchyPath
            );
        };

        // Function to find the correct insertion point
        const findInsertionPoint = (newPath) => {
            const container = document.getElementById('laporan-container');
            const allElements = Array.from(container.children);
            const items = getAllItems();
            
            for (let i = 0; i < items.length; i++) {
                const currentPath = items[i].dataset.hierarchyPath;
                if (compareHierarchyPaths(newPath, currentPath) < 0) {
                    const elementIndex = allElements.indexOf(items[i]);
                    return allElements[elementIndex];
                }
            }
            
            return null;
        };

        // Compare hierarchy paths for sorting
        const compareHierarchyPaths = (path1, path2) => {
            const parts1 = path1.split('.').map(n => parseInt(n));
            const parts2 = path2.split('.').map(n => parseInt(n));
            
            const maxLength = Math.max(parts1.length, parts2.length);
            
            for (let i = 0; i < maxLength; i++) {
                const num1 = parts1[i] || 0;
                const num2 = parts2[i] || 0;
                
                if (num1 !== num2) {
                    return num1 - num2;
                }
            }
            
            return 0;
        };

        // Get next number at a specific level
        const getNextNumber = (parentPath, isSubBab = false) => {
            const items = getAllItems();
            let maxNumber = 0;
            const targetLevel = parentPath ? parentPath.split('.').length + 1 : 1;
            
            items.forEach(item => {
                const itemPath = item.dataset.hierarchyPath;
                const itemParts = itemPath.split('.');
                
                if (itemParts.length === targetLevel) {
                    if (parentPath) {
                        const itemParentPath = itemParts.slice(0, -1).join('.');
                        if (itemParentPath === parentPath) {
                            const lastNumber = parseInt(itemParts[itemParts.length - 1]);
                            maxNumber = Math.max(maxNumber, lastNumber);
                        }
                    } else {
                        if (itemParts.length === 1) {
                            const number = parseInt(itemParts[0]);
                            maxNumber = Math.max(maxNumber, number);
                        }
                    }
                }
            });
            
            return maxNumber + 1;
        };

        // Find the last sub-BAB to determine where to add points
        const getLastSubBabPath = () => {
            const items = getAllItems();
            let lastSubBabPath = null;
            
            items.forEach(item => {
                if (item.dataset.type === 'sub-bab') {
                    lastSubBabPath = item.dataset.hierarchyPath;
                }
            });
            
            return lastSubBabPath;
        };

        const insertItemWithPrompt = (item, promptItem, insertBefore = null) => {
            const container = document.getElementById('laporan-container');
            
            if (insertBefore) {
                container.insertBefore(item, insertBefore);
                container.insertBefore(promptItem, insertBefore);
            } else {
                container.appendChild(item);
                container.appendChild(promptItem);
            }
        };

        const checkTitleFilled = async () => {
            const titleDisplay = document.getElementById('title-display');
            const titleInput = document.getElementById('document-title-input');
            
            // Cek apakah judul sudah tersimpan atau tidak
            if (titleDisplay.style.display === 'none' && titleInput.value.trim() === '') {
                await customModal('Mohon isi terlebih dahulu judul proposal Anda.');
                return false;
            }
            return true;
        };

        // Save item to database - sekarang mengirim hierarchy path sebagai type
        const saveItemToDB = async (hierarchyPath, judul, parentId = null) => {
            const response = await sendAjaxRequest({
                action: 'save_item',
                type: hierarchyPath, // Kirim hierarchy path sebagai type
                judul: judul,
                parent_id: parentId
            });
            
            return response;
        };

        // Update item in database
        const updateItemInDB = async (id, judul) => {
            const response = await sendAjaxRequest({
                action: 'update_item',
                id: id,
                judul: judul
            });
            
            return response;
        };

        // Delete item from database
        const deleteItemFromDB = async (id) => {
            const response = await sendAjaxRequest({
                action: 'delete_item',
                id: id
            });
            
            return response;
        };

        document.addEventListener('DOMContentLoaded', () => {
            const laporanContainer = document.getElementById('laporan-container');
            const addBabBtn = document.getElementById('add-bab-btn');
            const addSubBtn = document.getElementById('add-sub-btn');
            const addPointBtn = document.getElementById('add-point-btn');
            const downloadLaporanBtn = document.getElementById('download-laporan-btn');
            const downloadOptionsContainer = document.getElementById('download-options-container');
            const downloadDocBtn = document.getElementById('download-doc-btn');
            const downloadPdfBtn = document.getElementById('download-pdf-btn');

            // Document title event listeners
            const saveTitleBtn = document.getElementById('save-title-btn');
            const editTitleBtn = document.getElementById('edit-title-btn');
            const deleteTitleBtn = document.getElementById('delete-title-btn');
            const titleInput = document.getElementById('document-title-input');

            saveTitleBtn.addEventListener('click', saveDocumentTitle);
            editTitleBtn.addEventListener('click', editDocumentTitle);
            deleteTitleBtn.addEventListener('click', deleteDocumentTitle);
            
            // Allow Enter key to save title
            titleInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    saveDocumentTitle();
                }
            });

            // Load items on page load
            loadItemsFromDB();

            const addBabItem = async () => {
                if (!await checkTitleFilled()) return;

                const babNumber = getNextNumber(null);
                const babPath = babNumber.toString();
                
                const newBabItem = createLaporanItem('bab', babPath);
                const newPromptItem = createLaporanItem('prompt', babPath);
                
                insertItemWithPrompt(newBabItem, newPromptItem);
            };

            const addSubItem = async () => {
                if (!await checkTitleFilled()) return;
                
                const items = getAllItems();
                let lastBabPath = '1';
                
                items.forEach(item => {
                    if (item.dataset.type === 'bab') {
                        lastBabPath = item.dataset.hierarchyPath;
                    }
                });
                
                // Pengecekan apakah ada BAB yang sudah tersimpan
                const lastBabItem = items.find(item => item.dataset.hierarchyPath === lastBabPath);
                if (!lastBabItem || !lastBabItem.dataset.dbId) {
                    await customModal('Isi terlebih dahulu sesuai urutan (Simpan BAB terlebih dahulu).');
                    return;
                }

                const subNumber = getNextNumber(lastBabPath);
                const subPath = lastBabPath + '.' + subNumber;
                
                const newSubItem = createLaporanItem('sub-bab', subPath);
                const newPromptItem = createLaporanItem('prompt', subPath);
                
                const insertBefore = findInsertionPoint(subPath);
                insertItemWithPrompt(newSubItem, newPromptItem, insertBefore);
            };

            const addPointItem = async () => {
                if (!await checkTitleFilled()) return;
                
                const lastSubBabPath = getLastSubBabPath();

                if (!lastSubBabPath) {
                    await customModal('Isi terlebih dahulu sesuai urutan (Tambahkan sub-BAB terlebih dahulu).');
                    return;
                }

                // Pengecekan apakah sub-BAB terakhir sudah tersimpan
                const lastSubBabItem = getAllItems().find(item => item.dataset.hierarchyPath === lastSubBabPath);
                if (!lastSubBabItem || !lastSubBabItem.dataset.dbId) {
                    await customModal('Isi terlebih dahulu sesuai urutan (Simpan sub-BAB terlebih dahulu).');
                    return;
                }

                const pointNumber = getNextNumber(lastSubBabPath);
                const pointPath = lastSubBabPath + '.' + pointNumber;
                
                const newPointItem = createLaporanItem('point', pointPath);
                const newPromptItem = createLaporanItem('prompt', pointPath);
                
                const insertBefore = findInsertionPoint(pointPath);
                insertItemWithPrompt(newPointItem, newPromptItem, insertBefore);
            };

            addBabBtn.addEventListener('click', addBabItem);
            addSubBtn.addEventListener('click', addSubItem);
            addPointBtn.addEventListener('click', addPointItem);

            laporanContainer.addEventListener('click', async (event) => {
                const target = event.target;
                const item = target.closest('.laporan-item');
                if (!item) return;

                const textarea = item.querySelector('textarea');
                const savedContent = item.querySelector('.saved-text');
                const saveBtn = item.querySelector('.save-btn');
                const editBtn = item.querySelector('.edit-btn');
                const labelElement = item.querySelector('.item-label');
                
                if (target.classList.contains('add-child-btn')) {
                    // Add child to this point
                    const parentPath = item.dataset.hierarchyPath;
                    const childNumber = getNextNumber(parentPath);
                    const childPath = parentPath + '.' + childNumber;
                    
                    const newChildItem = createLaporanItem('point', childPath);
                    const newPromptItem = createLaporanItem('prompt', childPath);
                    
                    const insertBefore = findInsertionPoint(childPath);
                    insertItemWithPrompt(newChildItem, newPromptItem, insertBefore);
                    
                    await customModal(`Poin ${childPath} berhasil ditambahkan.`);
                    
                } else if (target.classList.contains('save-btn')) {
                    if (textarea.value.trim() === '') {
                        await customModal('Judul tidak boleh kosong.');
                        return;
                    }
                    
                    // Cek apakah judul proposal sudah terisi
                    const titleDisplay = document.getElementById('title-display');
                    if (titleDisplay.style.display === 'none') {
                        await customModal('Mohon isi terlebih dahulu judul proposal Anda.');
                        return;
                    }
                    
                    // Pengecekan urutan
                    const hierarchyPath = item.dataset.hierarchyPath;
                    const pathParts = hierarchyPath.split('.');
                    const type = getItemTypeFromPath(hierarchyPath);
                    
                    if (type !== 'bab') {
                        const parentPath = pathParts.slice(0, -1).join('.');
                        const parentItem = getAllItems().find(elem => elem.dataset.hierarchyPath === parentPath);
                        if (!parentItem || !parentItem.dataset.dbId) {
                            await customModal('Isi terlebih dahulu sesuai urutan (Simpan Judul Proposal terlebih dahulu).');
                            return;
                        }
                    }

                    // Set loading state
                    item.classList.add('loading');
                    
                    const value = textarea.value.trim();
                    const parentId = type !== 'bab' ?
                        (getAllItems().find(elem => elem.dataset.hierarchyPath === pathParts.slice(0, -1).join('.'))?.dataset.dbId || null) :
                        null;

                    const response = await saveItemToDB(hierarchyPath, value, parentId);
                    
                    // Remove loading state
                    item.classList.remove('loading');
                    
                    if (response.success) {
                        // Store database ID
                        item.dataset.dbId = response.id;
                        
                        // Update UI
                        textarea.style.display = 'none';
                        saveBtn.style.display = 'none';
                        
                        let formattedText = '';
                        if (labelElement) {
                            formattedText = `${labelElement.innerText} (${value})`;
                            labelElement.style.display = 'none';
                        } else {
                            formattedText = value;
                        }

                        savedContent.innerText = formattedText;
                        savedContent.style.display = 'block';
                        editBtn.style.display = 'block';
                        item.dataset.savedValue = value;

                        await customModal(`Item '${value}' berhasil disimpan.`);
                    } else {
                        await customModal('Gagal menyimpan: ' + response.error);
                    }

                } else if (target.classList.contains('edit-btn')) {
                    // Switch to edit mode
                    textarea.style.display = 'block';
                    // Find and hide save button if it exists
                    const saveButton = item.querySelector('.save-btn');
                    if(saveButton) saveButton.style.display = 'none';
                    editBtn.style.display = 'none';
                    savedContent.style.display = 'none';
                    
                    if (labelElement) {
                        labelElement.style.display = 'block';
                    }
                    
                    // Change save button behavior to update
                    let updateBtn = item.querySelector('.update-btn');
                    if (!updateBtn) {
                        updateBtn = document.createElement('button');
                        updateBtn.className = 'icon-btn update-btn bg-transparent border-none text-green-500 cursor-pointer text-xl transition-colors duration-200 hover:text-green-700';
                        updateBtn.title = 'Update';
                        updateBtn.innerHTML = '&#x2714;';
                        saveBtn.parentNode.insertBefore(updateBtn, editBtn.nextSibling);
                    }
                    updateBtn.style.display = 'block';

                } else if (target.classList.contains('update-btn')) {
                    if (textarea.value.trim() === '') {
                        await customModal('Judul tidak boleh kosong.');
                        return;
                    }
                    
                    // Set loading state
                    item.classList.add('loading');
                    
                    const value = textarea.value.trim();
                    const dbId = item.dataset.dbId;
                    
                    // Update in database
                    const response = await updateItemInDB(dbId, value);
                    
                    // Remove loading state
                    item.classList.remove('loading');
                    
                    if (response.success) {
                        // Update UI
                        textarea.style.display = 'none';
                        target.style.display = 'none';
                        
                        let formattedText = '';
                        if (labelElement) {
                            formattedText = `${labelElement.innerText} (${value})`;
                            labelElement.style.display = 'none';
                        } else {
                            formattedText = value;
                        }

                        savedContent.innerText = formattedText;
                        savedContent.style.display = 'block';
                        editBtn.style.display = 'block';
                        item.dataset.savedValue = value;
                        
                        await customModal(`Item '${value}' berhasil diupdate.`);
                    } else {
                        await customModal('Gagal mengupdate: ' + response.error);
                    }
                    
                } else if (target.classList.contains('delete-icon-btn')) {
                    const confirmed = await customModal('Apakah Anda yakin ingin menghapus item ini?', true);
                    if (confirmed) {
                        const dbId = item.dataset.dbId;
                        
                        if (dbId) {
                            // Set loading state
                            item.classList.add('loading');
                            
                            // Delete from database
                            const response = await deleteItemFromDB(dbId);
                            
                            if (response.success) {
                                // Remove from UI - find and remove corresponding prompt item too
                                const hierarchyPath = item.dataset.hierarchyPath;
                                const allElements = Array.from(document.getElementById('laporan-container').children);
                                
                                // Remove current item and its prompt
                                allElements.forEach(el => {
                                    if (el.dataset.hierarchyPath === hierarchyPath) {
                                        el.remove();
                                    }
                                });
                                
                                await customModal('Item berhasil dihapus.');
                            } else {
                                item.classList.remove('loading');
                                await customModal('Gagal menghapus: ' + response.error);
                            }
                        } else {
                            // Item not saved yet, just remove from UI
                            const hierarchyPath = item.dataset.hierarchyPath;
                            const allElements = Array.from(document.getElementById('laporan-container').children);
                            
                            allElements.forEach(el => {
                                if (el.dataset.hierarchyPath === hierarchyPath) {
                                    el.remove();
                                }
                            });
                            
                            await customModal('Item berhasil dihapus.');
                        }
                    }
                } else if (target.classList.contains('delete-btn')) {
                    const aiResultBox = item.querySelector('.ai-item-result-box');
                    textarea.value = '';
                    textarea.style.display = 'block';
                    aiResultBox.style.display = 'none';
                    item.querySelector('.send-btn').style.display = 'block';
                    item.querySelector('.delete-btn').style.display = 'block';

                    const editBtnPrompt = item.querySelector('.edit-btn');
                    if (editBtnPrompt) {
                        editBtnPrompt.remove();
                    }

                    await customModal('Prompt berhasil dihapus.');
                } else if (target.classList.contains('send-btn')) {
                    const item = event.target.closest('.laporan-item');
                    const textarea = item.querySelector('textarea');
                    const aiResultBox = item.querySelector('.ai-item-result-box');
                    
                    textarea.style.display = 'none';
                    item.querySelector('.send-btn').style.display = 'none';
                    item.querySelector('.delete-btn').style.display = 'none';

                    aiResultBox.innerHTML = '<span>Loading...</span>';
                    aiResultBox.style.display = 'flex';
                    
                    const editBtnPrompt = document.createElement('button');
                    editBtnPrompt.className = 'edit-btn px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-yellow-600 text-white hover:bg-yellow-700';
                    editBtnPrompt.innerText = 'Edit';
                    item.querySelector('.buttons').appendChild(editBtnPrompt);

                    setTimeout(async () => {
                        const generatedContent = `Ini adalah hasil dari prompt AI: ${textarea.value}`;
                        aiResultBox.innerHTML = `<span>${generatedContent}</span>`;
                        await customModal('Konten AI berhasil digenerasi.');
                        editBtnPrompt.style.display = 'block';
                    }, 1000);
                }
                
                if (event.target.classList.contains('edit-btn') && event.target.closest('.laporan-item').dataset.type === 'prompt') {
                    const item = event.target.closest('.laporan-item');
                    const textarea = item.querySelector('textarea');
                    const aiResultBox = item.querySelector('.ai-item-result-box');
                    const editBtnPrompt = item.querySelector('.edit-btn');
                    
                    textarea.style.display = 'block';
                    item.querySelector('.send-btn').style.display = 'block';
                    item.querySelector('.delete-btn').style.display = 'block';
                    
                    aiResultBox.style.display = 'none';
                    editBtnPrompt.remove();
                    
                    await customModal('Sekarang Anda dapat mengedit prompt.');
                }
            });
            
            const generateAndDownload = (fileType) => {
                const documentTitle = document.getElementById('display-title').textContent || 'Laporan Tidak Berjudul';
                let content = `Judul Laporan: ${documentTitle}\n\n`;
                const items = getAllItems();
                
                items.forEach(item => {
                    const type = item.dataset.type;
                    const path = item.dataset.hierarchyPath;
                    const value = item.dataset.savedValue || (item.querySelector('textarea') ? item.querySelector('textarea').value.trim() : '');

                    if (value) {
                        if (type === 'bab') {
                            content += `BAB ${path}: ${value}\n\n`;
                        } else if (type === 'sub-bab' || type === 'point') {
                            content += `${path}: ${value}\n`;
                        } else if (type === 'prompt') {
                            const aiResult = item.querySelector('.ai-item-result-box');
                            if (aiResult.style.display === 'flex' && aiResult.innerText.trim() !== 'Loading...') {
                                content += `Isi: ${aiResult.innerText.trim()}\n\n`;
                            } else {
                                content += `Isi: ${value}\n\n`;
                            }
                        }
                    }
                });
                
                let mimeType;
                let fileName;

                if (fileType === 'doc') {
                    mimeType = 'application/msword';
                    fileName = 'Laporan_Saya.doc';
                } else if (fileType === 'pdf') {
                    mimeType = 'text/plain';
                    fileName = 'Laporan_Saya.pdf';
                }

                const blob = new Blob([content], { type: mimeType });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                customModal(`Laporan berhasil diunduh sebagai ${fileName}!`);
                
                downloadOptionsContainer.style.display = 'none';
            };

            downloadLaporanBtn.addEventListener('click', () => {
                downloadOptionsContainer.style.display = downloadOptionsContainer.style.display === 'flex' ? 'none' : 'flex';
            });
            
            downloadDocBtn.addEventListener('click', () => {
                generateAndDownload('doc');
            });
            
            downloadPdfBtn.addEventListener('click', () => {
                generateAndDownload('pdf');
            });
        });
    </script>
</body>
</html>