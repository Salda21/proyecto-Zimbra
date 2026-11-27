document.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('clientChat');
  if (!root) return;

  const apiUrl = root.dataset.api;
  const csrfToken = root.dataset.csrf;
  const currentUserId = Number(root.dataset.currentUser);
  const searchInput = document.getElementById('contactSearch');
  const contactResults = document.getElementById('contactResults');
  const conversationList = document.getElementById('conversationList');
  const contactName = document.getElementById('contactName');
  const contactAvatar = document.getElementById('contactAvatar');
  const messageList = document.getElementById('messageList');
  const messageForm = document.getElementById('messageForm');
  const messageInput = document.getElementById('messageInput');
  const sendButton = document.getElementById('sendMessage');

  let selectedConversationId = null;
  let selectedContactName = '';
  let searchTimer;
  let polling = false;
  let initialConversationLoad = true;
  const knownLastMessages = new Map();

  async function request(url, options = {}) {
    const response = await fetch(url, {
      cache: 'no-store',
      ...options,
      headers: {
        ...(options.body ? { 'X-CSRF-Token': csrfToken } : {}),
        ...options.headers
      }
    });
    const raw = await response.text();
    let data;
    try {
      data = JSON.parse(raw);
    } catch {
      throw new Error('El servidor devolvió una respuesta no válida. Comprueba que PHP esté activo.');
    }

    if (!response.ok) {
      throw new Error(data.mensaje || 'No se pudo completar la operación.');
    }
    return data;
  }

  function showError(error) {
    const message = error instanceof Error ? error.message : 'Ocurrió un error inesperado.';
    if (window.ZCNotify) window.ZCNotify.error(message);
    else console.error(message);
  }

  async function loadContacts(query) {
    if (query.length < 2) {
      contactResults.hidden = true;
      contactResults.replaceChildren();
      return;
    }

    try {
      const data = await request(`${apiUrl}?accion=contactos&q=${encodeURIComponent(query)}`);
      contactResults.replaceChildren();

      if (!data.contactos.length) {
        const empty = document.createElement('li');
        empty.className = 'client-chat__empty';
        empty.textContent = 'No se encontraron clientes.';
        contactResults.append(empty);
      } else {
        data.contactos.forEach(contact => {
          const item = document.createElement('li');
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'client-chat__contact-option';

          const avatar = document.createElement('span');
          avatar.className = 'client-chat__avatar';
          avatar.textContent = contact.nombre.trim().charAt(0).toUpperCase();
          const name = document.createElement('span');
          name.textContent = contact.nombre;
          button.append(avatar, name);
          button.addEventListener('click', () => startConversation(Number(contact.id_usuario), contact.nombre));
          item.append(button);
          contactResults.append(item);
        });
      }
      contactResults.hidden = false;
    } catch (error) {
      showError(error);
    }
  }

  async function startConversation(contactId, name) {
    try {
      const body = new URLSearchParams({ accion: 'iniciar', id_contacto: String(contactId) });
      const data = await request(apiUrl, { method: 'POST', body });
      selectedConversationId = Number(data.id_conversacion);
      selectedContactName = name;
      searchInput.value = '';
      contactResults.hidden = true;
      contactResults.replaceChildren();
      messageInput.disabled = false;
      sendButton.disabled = false;
      updateThreadHeader();
      await refresh();
    } catch (error) {
      showError(error);
    }
  }

  function updateThreadHeader() {
    contactName.textContent = selectedContactName || 'Selecciona un chat';
    contactAvatar.textContent = selectedContactName ? selectedContactName.trim().charAt(0).toUpperCase() : 'Z';
  }

  function renderConversations(conversations) {
    conversationList.replaceChildren();

    if (!conversations.length) {
      const empty = document.createElement('li');
      empty.className = 'client-chat__empty';
      empty.textContent = 'Aún no tienes conversaciones. Busca un cliente para iniciar un chat.';
      conversationList.append(empty);
      return;
    }

    conversations.forEach(conversation => {
      const conversationId = Number(conversation.id_conversacion);
      const lastMessageId = Number(conversation.ultimo_id_mensaje || 0);
      const previousLastMessageId = knownLastMessages.get(conversationId);

      const esMensajeEntrante = Number(conversation.ultimo_remitente) !== currentUserId;
      const nuevaConversacionEntrante = previousLastMessageId === undefined &&
        !initialConversationLoad && esMensajeEntrante && Number(conversation.no_leidos) > 0;
      const nuevoMensajeEntrante = previousLastMessageId !== undefined &&
        lastMessageId > previousLastMessageId && esMensajeEntrante;

      if (nuevaConversacionEntrante || nuevoMensajeEntrante) {
        window.ZCNotify?.info(`Nuevo mensaje de ${conversation.nombre_contacto}.`, { title: 'Nuevo mensaje' });
      }
      knownLastMessages.set(conversationId, lastMessageId);

      const item = document.createElement('li');
      const button = document.createElement('button');
      button.type = 'button';
      button.className = `client-chat__conversation-option${conversationId === selectedConversationId ? ' is-active' : ''}`;

      const avatar = document.createElement('span');
      avatar.className = 'client-chat__avatar';
      avatar.textContent = conversation.nombre_contacto.trim().charAt(0).toUpperCase();
      const copy = document.createElement('span');
      copy.className = 'client-chat__conversation-copy';
      const name = document.createElement('strong');
      name.textContent = conversation.nombre_contacto;
      const preview = document.createElement('small');
      preview.textContent = conversation.ultimo_mensaje || 'Conversación iniciada';
      copy.append(name, preview);

      const meta = document.createElement('span');
      meta.className = 'client-chat__conversation-meta';
      if (conversation.fecha_ultimo_mensaje) {
        const time = document.createElement('time');
        time.textContent = conversation.fecha_ultimo_mensaje;
        meta.append(time);
      }
      if (Number(conversation.no_leidos) > 0) {
        const unread = document.createElement('span');
        unread.className = 'client-chat__unread';
        unread.textContent = String(conversation.no_leidos);
        meta.append(unread);
      }

      button.append(avatar, copy, meta);
      button.addEventListener('click', async () => {
        selectedConversationId = conversationId;
        selectedContactName = conversation.nombre_contacto;
        messageInput.disabled = false;
        sendButton.disabled = false;
        updateThreadHeader();
        await refresh();
      });
      item.append(button);
      conversationList.append(item);
    });
  }

  function renderMessages(messages) {
    messageList.replaceChildren();
    if (!messages.length) {
      const empty = document.createElement('p');
      empty.className = 'client-chat__placeholder';
      empty.textContent = 'Envía el primer mensaje para iniciar la conversación.';
      messageList.append(empty);
      return;
    }

    messages.forEach(message => {
      const item = document.createElement('article');
      item.className = `client-chat__message${message.mio ? ' is-mine' : ''}`;
      const content = document.createElement('p');
      content.textContent = message.contenido;
      const time = document.createElement('time');
      time.textContent = `${message.nombre_remitente} · ${message.fecha_envio}`;
      item.append(content, time);
      messageList.append(item);
    });
    messageList.scrollTop = messageList.scrollHeight;
  }

  async function refresh() {
    if (polling) return;
    polling = true;
    try {
      const data = await request(`${apiUrl}?accion=conversaciones`);
      renderConversations(data.conversaciones);
      initialConversationLoad = false;

      if (selectedConversationId) {
        const selected = data.conversaciones.find(
          conversation => Number(conversation.id_conversacion) === selectedConversationId
        );
        if (selected) selectedContactName = selected.nombre_contacto;
        updateThreadHeader();
        const readBody = new URLSearchParams({
          accion: 'leer',
          id_conversacion: String(selectedConversationId)
        });
        await request(apiUrl, { method: 'POST', body: readBody });
        const messages = await request(
          `${apiUrl}?accion=mensajes&id_conversacion=${encodeURIComponent(selectedConversationId)}`
        );
        renderMessages(messages.mensajes);
      }
    } catch (error) {
      if (!initialConversationLoad) console.error('Error al actualizar los chats:', error);
      else showError(error);
    } finally {
      polling = false;
    }
  }

  searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadContacts(searchInput.value.trim()), 250);
  });

  messageForm.addEventListener('submit', async event => {
    event.preventDefault();
    const contenido = messageInput.value.trim();
    if (!selectedConversationId || !contenido) return;

    sendButton.disabled = true;
    try {
      const body = new URLSearchParams({
        accion: 'enviar',
        id_conversacion: String(selectedConversationId),
        contenido
      });
      await request(apiUrl, { method: 'POST', body });
      messageInput.value = '';
      await refresh();
    } catch (error) {
      showError(error);
    } finally {
      sendButton.disabled = !selectedConversationId;
      messageInput.focus();
    }
  });

  refresh();
  window.setInterval(refresh, 3000);
});
