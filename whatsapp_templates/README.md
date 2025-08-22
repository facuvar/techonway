# Plantillas HSM de WhatsApp para TechonWay

Este directorio contiene las plantillas HSM (Highly Structured Messages) en formato JSON que deben ser enviadas a META para aprobación.

## ¿Por qué necesitamos estas plantillas?

WhatsApp Business API requiere que después de 24 horas sin conversación, solo se puedan enviar mensajes usando plantillas pre-aprobadas por META. Esto garantiza que los técnicos siempre reciban notificaciones, sin importar cuándo fue la última interacción.

## Plantillas Incluidas

1. **nuevo_ticket.json** - Para notificar asignación de nuevo ticket
2. **reprogramacion_cita.json** - Para notificar cambios en citas programadas

## Proceso de Implementación

1. Enviar estos JSONs a META para aprobación a través del Business Manager
2. Una vez aprobadas, actualizar el código para usar estas plantillas cuando sea necesario
3. El sistema detectará automáticamente cuándo usar plantillas vs mensajes directos

## Variables del Sistema

Las plantillas usan las siguientes variables del sistema TechonWay:
- `{{1}}` - ID del ticket
- `{{2}}` - Nombre del cliente  
- `{{3}}` - Dirección del cliente
- `{{4}}` - Descripción del ticket (truncada)
- `{{5}}` - Fecha de la cita
- `{{6}}` - Hora de la cita
- `{{7}}` - Código de seguridad
