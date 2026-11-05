// Interacciones pequeñas del sitio: formularios dinámicos, edición de datos y consentimiento de cookies.
// Abre el formulario de usuario y carga los valores actuales sin enviarlos.
// Los cambios solo llegan al servidor cuando se pulsa Guardar.
function editUser(u, cats) {
    const form=document.getElementById('userModal');
    document.getElementById('user_id').value=u.id;
    ['usuario','cedula','nombre','apellido','correo','celular'].forEach(k=>document.getElementById('u_'+k).value=u[k]||'');
    document.getElementById('u_clave').value='';
    document.getElementById('u_rol').value=u.rol_id;
    form.querySelectorAll('input[name="categorias[]"]').forEach(x=>x.checked=cats.map(Number).includes(Number(x.value)));
    bootstrap.Modal.getOrCreateInstance(form).show();
}
// Completa la ventana de documento. Cerrar con la X no ejecuta ningún guardado.
function editDoc(d,cats) {
    const form=document.getElementById('docModal');
    document.getElementById('doc_id').value=d.id;
    document.getElementById('d_titulo').value=d.titulo;
    document.getElementById('d_descripcion').value=d.descripcion||'';
    // El documento ya no tiene una acción de baja; solo se editan sus datos y archivo.
    form.querySelectorAll('input[name="categorias[]"]').forEach(x=>x.checked=cats.map(Number).includes(Number(x.value)));
    bootstrap.Modal.getOrCreateInstance(form).show();
}
// El constructor mantiene temporalmente las preguntas en memoria hasta que se guarda la encuesta.
let surveyQuestions=[];
// Prepara un formulario vacío para crear una encuesta nueva.
function newSurvey() {
    surveyQuestions=[];
    document.getElementById('s_id').value='';
    document.getElementById('s_titulo').value='';
    document.getElementById('s_descripcion').value='';
    document.getElementById('questionsList').innerHTML='';
    document.getElementById('surveyForm').querySelectorAll('input[name="categorias[]"]').forEach(x=>x.checked=false);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('surveyModal')).show();
}

// Carga una encuesta existente en el constructor para editarla.
function editSurvey(s, questions, cats) {
    surveyQuestions=JSON.parse(JSON.stringify(questions||[]));
    document.getElementById('s_id').value=s.id;
    document.getElementById('s_titulo').value=s.titulo||'';
    document.getElementById('s_descripcion').value=s.descripcion||'';
    document.querySelector('#surveyForm [name="publico"]').value=s.publico;
    document.querySelector('#surveyForm [name="estado"]').value=s.estado;
    document.getElementById('surveyForm').querySelectorAll('input[name="categorias[]"]').forEach(x=>x.checked=cats.map(Number).includes(Number(x.value)));
    renderQuestions();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('surveyModal')).show();
}

// Agrega una pregunta del tipo elegido a la lista temporal del constructor.
function addQuestion(type) {
    const labels={unica:'Opción única',multiple:'Varias opciones',select:'Lista desplegable',valoracion:'Valoración 1 a 5',texto:'Texto breve'};
    surveyQuestions.push({tipo:type,texto:'',obligatoria:true,opciones:(['unica','multiple','select'].includes(type)?['Opción 1','Opción 2']:[])});
    renderQuestions();
}
// Vuelve a dibujar las preguntas y sus opciones a partir del estado temporal.
function renderQuestions() {
    const box=document.getElementById('questionsList'); if(!box)return;
    box.innerHTML='';
    surveyQuestions.forEach((q,i)=>{
        const card=document.createElement('div');card.className='question-editor card mb-2';
        const title=document.createElement('div');title.className='card-header d-flex justify-content-between';
        title.textContent=(i+1)+'. '+({unica:'Opción única',multiple:'Varias opciones',select:'Lista desplegable',valoracion:'Valoración 1 a 5',texto:'Texto breve'}[q.tipo]);
        const controls=document.createElement('span');
        controls.innerHTML='<button type="button" class="btn btn-sm btn-outline-secondary me-1">↑</button><button type="button" class="btn btn-sm btn-outline-secondary me-1">↓</button><button type="button" class="btn btn-sm btn-outline-danger">Quitar</button>';
        const bs=controls.querySelectorAll('button');
        bs[0].onclick=()=>{if(i>0){[surveyQuestions[i-1],surveyQuestions[i]]=[surveyQuestions[i],surveyQuestions[i-1]];renderQuestions();}};
        bs[1].onclick=()=>{if(i<surveyQuestions.length-1){[surveyQuestions[i+1],surveyQuestions[i]]=[surveyQuestions[i],surveyQuestions[i+1]];renderQuestions();}};
        bs[2].onclick=()=>{surveyQuestions.splice(i,1);renderQuestions();};
        title.appendChild(controls);card.appendChild(title);
        const body=document.createElement('div');body.className='card-body';
        const input=document.createElement('input');input.className='form-control mb-2';input.placeholder='Escriba la pregunta';input.value=q.texto;input.oninput=()=>q.texto=input.value;body.appendChild(input);
        const required=document.createElement('label');required.className='form-check';
        const cb=document.createElement('input');cb.type='checkbox';cb.className='form-check-input';cb.checked=q.obligatoria;cb.onchange=()=>q.obligatoria=cb.checked;
        required.append(cb,document.createTextNode(' Pregunta obligatoria'));body.appendChild(required);
        if(['unica','multiple','select'].includes(q.tipo)){
            q.opciones.forEach((op,j)=>{const row=document.createElement('div');row.className='input-group mt-2';const inp=document.createElement('input');inp.className='form-control';inp.value=op;inp.oninput=()=>q.opciones[j]=inp.value;const del=document.createElement('button');del.type='button';del.className='btn btn-outline-danger';del.textContent='×';del.onclick=()=>{q.opciones.splice(j,1);renderQuestions();};row.append(inp,del);body.appendChild(row);});
            const add=document.createElement('button');add.type='button';add.className='btn btn-sm btn-outline-primary mt-2';add.textContent='Agregar opción';add.onclick=()=>{q.opciones.push('Nueva opción');renderQuestions();};body.appendChild(add);
        }
        card.appendChild(body);box.appendChild(card);
    });
}
document.addEventListener('DOMContentLoaded',()=>{
    const form=document.getElementById('surveyForm');
    if(form)form.addEventListener('submit',e=>{
        if(!surveyQuestions.length){e.preventDefault();alert('Agregue al menos una pregunta.');return;}
        if(surveyQuestions.some(q=>!q.texto.trim())){e.preventDefault();alert('Escriba el texto de todas las preguntas.');return;}
        if(surveyQuestions.some(q=>['unica','multiple','select'].includes(q.tipo)&&q.opciones.filter(x=>x.trim()).length<2)){e.preventDefault();alert('Cada pregunta con opciones debe tener al menos dos.');return;}
        document.getElementById('preguntas_json').value=JSON.stringify(surveyQuestions);
    });
    if(!document.cookie.split('; ').some(x=>x.startsWith('sigsm_cookies='))){
        const notice=document.createElement('div');notice.className='cookie-note';notice.innerHTML='<span>Este sitio utiliza cookies técnicas para mantener la sesión y recordar su aceptación.</span> <button class="btn btn-sm btn-light">Aceptar</button>';
        notice.querySelector('button').onclick=()=>{document.cookie='sigsm_cookies=aceptadas; max-age=31536000; path=/; samesite=lax';notice.remove();};document.body.appendChild(notice);
    }
});


// Conserva la posición al cambiar de pestaña o enviar formularios del panel.
// Así la página no vuelve al principio después de guardar o cambiar de sección.
(function(){
  const KEY='sigsm_scroll_position';
  document.addEventListener('click',e=>{
    const link=e.target.closest('a[href]');
    if(link && /[?&]tab=/.test(link.href)) sessionStorage.setItem(KEY,String(window.scrollY));
  });
  document.addEventListener('submit',e=>{
    const form=e.target;
    if(form.closest('#userModal,#docModal,#surveyModal') || form.querySelector('input[name="action"]')) sessionStorage.setItem(KEY,String(window.scrollY));
  },true);
  window.addEventListener('DOMContentLoaded',()=>{
    const saved=sessionStorage.getItem(KEY);
    if(saved!==null){sessionStorage.removeItem(KEY);requestAnimationFrame(()=>window.scrollTo(0,Number(saved)||0));}
    document.querySelectorAll('.toast-notice').forEach(notice=>{
      window.setTimeout(()=>{notice.style.transition='opacity .35s, transform .35s';notice.style.opacity='0';notice.style.transform='translateY(10px)';window.setTimeout(()=>notice.remove(),400);},4200);
    });
  });
})();
