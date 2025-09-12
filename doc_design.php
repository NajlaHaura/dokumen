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

// Ambil judul dokumen terakhir user
$query = "SELECT judul 
          FROM judul_dokumen 
          WHERE user_id = ? 
          ORDER BY id DESC 
          LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$judul_proposal = "[ Belum ada judul ]";
if ($row = $result->fetch_assoc()) {
    $judul_proposal = $row['judul'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal UI</title>
    <!-- Load Tailwind CSS from CDN -->
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
        
        .proposal-item {
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-100 flex justify-center items-center min-h-screen p-5">
    <div class="container w-full max-w-4xl bg-white rounded-xl shadow-lg p-8">
        <div class="header flex justify-between items-center mb-5 border-b pb-4 border-gray-200">
            <h1 class="text-2xl font-semibold text-gray-800 m-0">Proposal <span id="proposal-title"><?php echo htmlspecialchars($judul_proposal); ?></span></h1>
        </div>
        
        <div id="proposal-container">
            <!-- BAB and sub-BAB items will be dynamically added here -->
        </div>

        <div class="bottom-buttons flex flex-col items-center gap-4 mt-6">
            <div class="add-buttons-container flex gap-4 w-full justify-center">
                <button class="add-bab-btn px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-gray-700 text-white hover:bg-gray-800" id="add-bab-btn">Tambahkan BAB Baru</button>
                <button class="add-sub-btn px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-gray-700 text-white hover:bg-gray-800" id="add-sub-btn">Tambahkan sub-BAB</button>
                <button class="add-point-btn px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-gray-700 text-white hover:bg-gray-800" id="add-point-btn">Tambahkan Poin</button>
            </div>
            <button class="download-btn px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-yellow-600 text-white w-full max-w-[250px] hover:bg-yellow-700" id="download-proposal-btn">Download Proposal</button>
            <div id="download-options-container" class="download-options hidden flex-col gap-2 w-full max-w-[250px]">
                <button class="px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-yellow-600 text-white hover:bg-yellow-700" id="download-doc-btn">DOC</button>
                <button class="px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-yellow-600 text-white hover:bg-yellow-700" id="download-pdf-btn">PDF</button>
            </div>
        </div>
    </div>
    
    <!-- Custom Modal for alerts and confirms -->
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
        // PHP file serves the HTML and client-side JavaScript. All logic is handled below.
        
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

        let counters = {
            bab: 1,
            subBab: 1,
            point: 1
        };

        const createProposalItem = (type, babIndex, subBabIndex = null, pointIndex = null) => {
            const newDiv = document.createElement('div');
            let classList = ['proposal-item', 'flex', 'flex-wrap', 'items-center', 'gap-4', 'mb-4'];
            let labelText;
            let placeholderText;
            
            newDiv.dataset.type = type;
            newDiv.dataset.babIndex = babIndex;
            if (subBabIndex !== null) {
                newDiv.dataset.subBabIndex = subBabIndex;
            }
            if (pointIndex !== null) {
                newDiv.dataset.pointIndex = pointIndex;
            }

            if (type === 'bab') {
                labelText = `BAB ${babIndex}`;
                placeholderText = 'Masukkan judul BAB di sini';
            } else if (type === 'sub-bab') {
                labelText = `${babIndex}.${subBabIndex}`;
                placeholderText = 'Masukkan judul sub-BAB di sini';
                classList.push('pl-10');
            } else if (type === 'sub-sub-bab') {
                labelText = `${babIndex}.${subBabIndex}.${pointIndex}`;
                placeholderText = 'Masukkan judul poin di sini';
                classList.push('pl-20');
            } else if (type === 'prompt') {
                labelText = ''; // Prompt has no label
                if (pointIndex !== null) {
                    placeholderText = `Isi prompt ${babIndex}.${subBabIndex}.${pointIndex} [...]`;
                } else if (subBabIndex !== null) {
                    placeholderText = `Isi prompt sub-Bab ${babIndex}.${subBabIndex} [...]`;
                } else {
                    placeholderText = `Isi prompt BAB ${babIndex} [...]`;
                }
                // Add divider before prompt
                const divider = document.createElement('div');
                divider.className = 'divider';
                document.getElementById('proposal-container').appendChild(divider);
            }
            
            newDiv.className = classList.join(' ');

            let innerHTML = `
                ${labelText ? `<div class="item-label font-semibold whitespace-nowrap">${labelText}</div>` : ''}
                <textarea class="${type === 'prompt' ? '' : 'input-field bg-yellow-600 text-gray-800 font-medium placeholder-gray-800'} flex-grow p-3 border border-gray-300 rounded-lg text-sm text-gray-700 bg-gray-50 resize-y min-h-[40px] focus:outline-none focus:border-yellow-600 focus:ring-2 focus:ring-yellow-200" placeholder="${placeholderText}"></textarea>
                <div class="saved-text flex-grow p-3 font-medium text-gray-800 hidden"></div>
            `;

            if (type === 'bab' || type === 'sub-bab' || type === 'sub-sub-bab') {
                innerHTML += `
                    <div class="icons flex gap-2">
                        <button class="icon-btn save-btn bg-transparent border-none text-green-500 cursor-pointer text-xl transition-colors duration-200 hover:text-green-700">&#x2714;</button>
                        <button class="icon-btn delete-icon-btn bg-transparent border-none text-red-500 cursor-pointer text-xl transition-colors duration-200 hover:text-red-700">&#x2326;</button>
                    </div>
                `;
            } else if (type === 'prompt') {
                innerHTML += `
                    <div class="buttons flex flex-col gap-3" style="align-self: flex-start; margin-top: 0;">
                        <button class="send-btn px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-gray-700 text-white hover:bg-gray-800">Send</button>
                        <button class="delete-btn px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-gray-500 text-white hover:bg-gray-600">Delete</button>
                    </div>
                `;
                innerHTML += `<div class="ai-item-result-box bg-gray-100 border border-gray-200 rounded-lg min-h-[100px] p-4 mt-4 flex justify-center items-center text-gray-400 italic whitespace-pre-wrap w-full"><span>Hasil dari prompt AI</span></div>`;
            }

            newDiv.innerHTML = innerHTML;
            return newDiv;
        };

        document.addEventListener('DOMContentLoaded', () => {
            const proposalContainer = document.getElementById('proposal-container');
            const addBabBtn = document.getElementById('add-bab-btn');
            const addSubBtn = document.getElementById('add-sub-btn');
            const addPointBtn = document.getElementById('add-point-btn');
            const downloadProposalBtn = document.getElementById('download-proposal-btn');
            const downloadOptionsContainer = document.getElementById('download-options-container');
            const downloadDocBtn = document.getElementById('download-doc-btn');
            const downloadPdfBtn = document.getElementById('download-pdf-btn');

            const addBabItem = () => {
                const newBabItem = createProposalItem('bab', counters.bab);
                proposalContainer.appendChild(newBabItem);
                counters.bab++;
                counters.subBab = 1;
                counters.point = 1;
            };

            const addSubItem = () => {
                const babIndex = counters.bab > 1 ? counters.bab - 1 : 1;
                if (babIndex === 0) {
                    addBabItem();
                    return addSubItem();
                }
                const newSubBabItem = createProposalItem('sub-bab', babIndex, counters.subBab);
                proposalContainer.appendChild(newSubBabItem);

                const newPromptItem = createProposalItem('prompt', babIndex, counters.subBab);
                proposalContainer.appendChild(newPromptItem);

                counters.subBab++;
                counters.point = 1;
            };

            const addPointItem = () => {
                const babIndex = counters.bab > 1 ? counters.bab - 1 : 1;
                const subBabIndex = counters.subBab > 1 ? counters.subBab - 1 : 1;
                if (subBabIndex === 0) {
                    addSubItem();
                    return addPointItem();
                }

                const newPointItem = createProposalItem('sub-sub-bab', babIndex, subBabIndex, counters.point);
                proposalContainer.appendChild(newPointItem);

                const newPromptItem = createProposalItem('prompt', babIndex, subBabIndex, counters.point);
                proposalContainer.appendChild(newPromptItem);

                counters.point++;
            };

            // Initial items on page load
            addBabItem();
            addSubItem();

            addBabBtn.addEventListener('click', addBabItem);
            addSubBtn.addEventListener('click', addSubItem);
            addPointBtn.addEventListener('click', addPointItem);

            proposalContainer.addEventListener('click', async (event) => {
                const target = event.target;
                const item = target.closest('.proposal-item');
                if (!item) return;

                const textarea = item.querySelector('textarea');
                const savedContent = item.querySelector('.saved-text');
                const saveBtn = item.querySelector('.save-btn');
                const labelElement = item.querySelector('.item-label');
                
                if (target.classList.contains('save-btn')) {
                    if (textarea.value.trim() === '') {
                        await customModal('Judul tidak boleh kosong.');
                        return;
                    }
                    
                    textarea.style.display = 'none';
                    saveBtn.style.display = 'none';
                    
                    const value = textarea.value.trim();
                    let formattedText = '';
                    if (labelElement) {
                        formattedText = `${labelElement.innerText} (${value})`;
                    } else {
                        formattedText = value;
                    }

                    savedContent.innerText = formattedText;
                    savedContent.style.display = 'block';
                    item.dataset.savedValue = value;
                    
                    if (labelElement) {
                        labelElement.style.display = 'none';
                    }

                    await customModal(`Item '${value}' berhasil disimpan.`);

                } else if (target.classList.contains('delete-icon-btn')) {
                    const confirmed = await customModal('Apakah Anda yakin ingin menghapus judul ini?', true);
                    if (confirmed) {
                        textarea.value = '';
                        textarea.style.display = 'block';
                        
                        savedContent.innerText = '';
                        savedContent.style.display = 'none';
                        delete item.dataset.savedValue;

                        saveBtn.style.display = 'block';

                        if (labelElement) {
                            labelElement.style.display = 'block';
                        }
                        
                        await customModal('Judul berhasil dihapus.');
                    }
                } else if (target.classList.contains('delete-btn')) {
                    const aiResultBox = item.querySelector('.ai-item-result-box');
                    textarea.value = '';
                    textarea.style.display = 'block';
                    aiResultBox.style.display = 'none';
                    item.querySelector('.send-btn').style.display = 'block';
                    item.querySelector('.delete-btn').style.display = 'block';
                    
                    const editBtn = item.querySelector('.edit-btn');
                    if (editBtn) {
                        editBtn.remove();
                    }

                    await customModal('Prompt berhasil dihapus.');
                } else if (target.classList.contains('send-btn')) {
                    const item = event.target.closest('.proposal-item');
                    const textarea = item.querySelector('textarea');
                    const aiResultBox = item.querySelector('.ai-item-result-box');
                    
                    textarea.style.display = 'none';
                    item.querySelector('.send-btn').style.display = 'none';
                    item.querySelector('.delete-btn').style.display = 'none';

                    aiResultBox.innerHTML = '<span>Loading...</span>';
                    aiResultBox.style.display = 'flex';
                    
                    const editBtn = document.createElement('button');
                    editBtn.className = 'edit-btn px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-all duration-200 shadow-md bg-yellow-600 text-white hover:bg-yellow-700';
                    editBtn.innerText = 'Edit';
                    item.querySelector('.buttons').appendChild(editBtn);

                    setTimeout(async () => {
                        const generatedContent = `Ini adalah hasil dari prompt AI: ${textarea.value}`;
                        aiResultBox.innerHTML = `<span>${generatedContent}</span>`;
                        await customModal('Konten AI berhasil digenerasi.');
                        editBtn.style.display = 'block';
                    }, 1000);
                }
                
                if (event.target.classList.contains('edit-btn')) {
                    const item = event.target.closest('.proposal-item');
                    const textarea = item.querySelector('textarea');
                    const aiResultBox = item.querySelector('.ai-item-result-box');
                    const editBtn = item.querySelector('.edit-btn');
                    
                    textarea.style.display = 'block';
                    item.querySelector('.send-btn').style.display = 'block';
                    item.querySelector('.delete-btn').style.display = 'block';
                    
                    aiResultBox.style.display = 'none';
                    editBtn.remove();
                    
                    await customModal('Sekarang Anda dapat mengedit prompt.');
                }
            });
            
            const generateAndDownload = (fileType) => {
                let content = `Judul Proposal: ${document.getElementById('proposal-title').innerText}\n\n`;
                const items = proposalContainer.querySelectorAll('.proposal-item');
                items.forEach(item => {
                    const type = item.dataset.type;
                    const value = item.dataset.savedValue || (item.querySelector('textarea') ? item.querySelector('textarea').value.trim() : '');

                    if (value) {
                        if (type === 'bab') {
                            content += `BAB ${item.dataset.babIndex}: ${value}\n\n`;
                        } else if (type === 'sub-bab') {
                            content += `${item.dataset.babIndex}.${item.dataset.subBabIndex}: ${value}\n`;
                        } else if (type === 'sub-sub-bab') {
                            content += `${item.dataset.babIndex}.${item.dataset.subBabIndex}.${item.dataset.pointIndex}: ${value}\n`;
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
                    fileName = 'Proposal_Saya.doc';
                } else if (fileType === 'pdf') {
                    mimeType = 'text/plain'; // Real PDF requires a library, this is a placeholder
                    fileName = 'Proposal_Saya.pdf';
                }

                const blob = new Blob([content], { type: mimeType });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                customModal(`Proposal berhasil diunduh sebagai ${fileName}!`);
                
                downloadOptionsContainer.style.display = 'none';
            };

            downloadProposalBtn.addEventListener('click', () => {
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