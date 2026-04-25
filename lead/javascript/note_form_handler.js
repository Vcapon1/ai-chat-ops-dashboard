
/**
 * Handle note form submission
 */
function setupNoteFormHandler() {
    const noteForm = document.getElementById('add-note-form');
    if (noteForm) {
        noteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const leadId = formData.get('client_id');
            
            // Validate note content
            const noteContent = formData.get('note_content');
            if (!noteContent || noteContent.trim() === '') {
                showNotification('O conteúdo da anotação não pode estar vazio', 'error');
                return;
            }
            
            // Disable submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="animate-pulse">Enviando...</span>';
            
            // Add action parameter for backend processing
            formData.append('ajax_action', 'add_note');
            
            // Better debug logging
            console.log('Formulário enviando:', {
                leadId: leadId,
                noteContent: noteContent,
                ajaxAction: formData.get('ajax_action'),
                formDataEntries: Array.from(formData.entries()).map(entry => ({ key: entry[0], value: entry[1] }))
            });
            
            fetch('lead_details.php?id=' + leadId, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Status da resposta:', response.status);
                if (!response.ok) {
                    throw new Error('Erro na resposta da rede: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Resposta recebida:', data);
                
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                
                if (data.success) {
                    // Clear the form
                    noteForm.reset();
                    
                    // Add the new note to the list
                    const notesContainer = document.getElementById('notes-container');
                    const noNotesMessage = document.getElementById('no-notes-message');
                    
                    // Remove the "no notes" message if it exists
                    if (noNotesMessage) {
                        noNotesMessage.remove();
                    }
                    
                    // Create new note element
                    const noteElement = document.createElement('div');
                    noteElement.className = 'bg-gray-700/50 rounded-lg p-4';
                    noteElement.innerHTML = `
                        <p class="text-gray-300 whitespace-pre-line">${data.note.content}</p>
                        <p class="text-xs text-gray-500 mt-2">${data.note.date}</p>
                    `;
                    
                    // Add to beginning of list
                    if (notesContainer.firstChild) {
                        notesContainer.insertBefore(noteElement, notesContainer.firstChild);
                    } else {
                        notesContainer.appendChild(noteElement);
                    }
                    
                    // Show success notification
                    showNotification(data.message || 'Anotação adicionada com sucesso', 'success');
                } else {
                    // Show error notification
                    showNotification(data.message || 'Erro ao adicionar anotação', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                // Show error notification
                showNotification('Erro ao processar solicitação: ' + error.message, 'error');
            });
        });
    } else {
        console.error('Form de anotações não encontrado');
    }
}
