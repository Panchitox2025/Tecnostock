// Validación en el navegador para los formularios con el atributo data-validar.
// Marca los campos inválidos, muestra el motivo y evita enviar el formulario.
// Las mismas reglas se repiten en PHP, porque el servidor nunca debe confiar
// solo en lo que valida el navegador.
document.querySelectorAll('form[data-validar]').forEach((form) => {
    const campos = form.querySelectorAll('input, select, textarea');

    // Mensajes en español, sin depender del idioma del navegador
    const mensaje = (campo) => {
        const v = campo.validity;
        if (v.valueMissing) {
            return campo.tagName === 'SELECT' ? 'Selecciona una opción.' : 'Este campo es obligatorio.';
        }
        if (v.rangeUnderflow) {
            return `El valor debe ser ${campo.min} o mayor.`;
        }
        if (v.stepMismatch) {
            return campo.step === '1' ? 'Ingresa un número entero.' : 'Ingresa un número válido.';
        }
        if (v.badInput || v.typeMismatch) {
            return 'Ingresa un valor válido.';
        }
        return 'Revisa este campo.';
    };

    const revisar = (campo) => {
        const valido = campo.checkValidity();
        campo.classList.toggle('invalido', !valido);
        const ayuda = campo.closest('.campo')?.querySelector('.ayuda');
        if (ayuda) {
            ayuda.textContent = valido ? '' : mensaje(campo);
        }
        return valido;
    };

    form.addEventListener('submit', (evento) => {
        let todoValido = true;
        campos.forEach((campo) => {
            if (!revisar(campo)) {
                todoValido = false;
            }
        });
        if (!todoValido) {
            evento.preventDefault();
            form.querySelector('.invalido')?.focus();
        }
    });

    // Quita el error apenas el usuario corrige el campo
    campos.forEach((campo) => {
        campo.addEventListener('input', () => {
            if (campo.classList.contains('invalido')) {
                revisar(campo);
            }
        });
    });
});
