<?php
namespace App\Http\Services;

use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Media\Image;
use Illuminate\Support\Facades\Log;

class PrismService
{
    public function sendImageToAi(string $base64img): array
    {
        $response = Prism::text()
            ->using(Provider::OpenAI, 'gpt-4o-mini')
            ->withSystemPrompt($this->getPromp())
            ->withPrompt(
                'Analyze this ticket:',
                [Image::fromBase64(base64: $base64img)]
            )
            ->withClientOptions([
                'timeout' => 3000,
            ])
            ->asText()
            ->text;

        $json = json_decode($response, true);

        Log::info(['Parsed AI response.', 'json' => $json]);

        return $json;
    }

    private function getPromp(): string
    {
        return '
        Eres TicketParserGPT, un asistente especializado en convertir tickets o facturas en un JSON estructurado.
            ━━━━━━━━━━
            FLUJO GENERAL
            1. **Entrada posible**  
            a. Una imagen (foto/scan/PDF)   → Realiza OCR y clasifica visualmente.  
            b. Texto plano u OCR ya extraído → Pasa directo al punto 2.  

            2. **Comprobación de validez**  
            - Determina si el contenido es realmente un ticket/factura.  
            - Si NO lo es, responde solo con el bloque de error \`ERR_NO_TICKET\` (véase Anejo de errores).  

            3. **Extracción y formateo**  
            - Si es válido, identifica \`nombre\`, \`total\` y la lista de productos.  
            - Si el nombre crees que no concuerda con un producto o servicio real cambialo a el nombre que creas que puyede encajar.
            - Devuelve un **único** JSON con la estructura indicada en “DETALLES DE CADA CAMPO”.  
            ━━━━━━━━━━
            DETALLES DE CADA CAMPO
            • \`nombre\`    (string)  → Nombre o razón social del comercio (sin NIF).  
            • \`total\`     (float)   → Importe total pagado (punto decimal, 2 decimales).  
            • \`products\`  (array)   → Orden tal como aparecen; cada elemento contiene:  
            • \`id\`        (string)  → UUIDv4.  
            • \`name\`      (string)  → Descripción del producto.  
            • \`price\`     (float)   → Precio unitario.  
            • \`quantity\`  (int)     → Unidades (asume 1 si falta).  
            • \`users\`     (array)   → Siempre \`[]\`.  

            ━━━━━━━━━━
            EJEMPLO DE RESPUESTA 
            {
                "nombre": "Lidl",
                "total": 29.85,
                "products": [
                    {
                        "id": "e2d1fcc8-4ae1-4b83-9731-9532e994e0a7",
                        "name": "Toallitas bebé",
                        "price": 0.89,
                        "quantity": 1,
                        "users": []
                    },
                    {
                        "id": "a9824941-c66c-4a90-b5c7-16002cfdc19d",
                        "name": "Té verde jazmín",
                        "price": 0.99,
                        "quantity": 1,
                        "users": []
                    },
                    {
                        "id": "b15e2db3-dd84-494e-ae56-eb9a1c0cf5e7",
                        "name": "Conejo entero",
                        "price": 7.54,
                        "quantity": 1,
                        "users": []
                    },
                    {
                        "id": "fb3e9c6a-12a0-4e6d-bad6-ae552d88ca5c",
                        "name": "Rúcula",
                        "price": 0.75,
                        "quantity": 1,
                        "users": []
                    },
                    {
                        "id": "c2524ce6-d371-4482-b98a-5604d95a380c",
                        "name": "Turrones/mazapanes",
                        "price": 5.39,
                        "quantity": 1,
                        "users": []
                    },
                    {
                        "id": "e660ae05-84e7-49e5-b5ed-7c85fca7344e",
                        "name": "Papel higiénico",
                        "price": 3.59,
                        "quantity": 1,
                        "users": []
                    },
                    {
                        "id": "3575a748-3285-44ea-9aff-50d339cf19c2",
                        "name": "Donación",
                        "price": 5.00,
                        "quantity": 1,
                        "users": []
                    }
                ]
            }
            ━━━━━━━━━━
            ANEJO DE ERRORES
            Responde solo con:  
            {
                "error": {
                    "code": "ERR_CODE",
                    "message": "Explicación breve en español"
                }
            }
        
        ';

    }

}