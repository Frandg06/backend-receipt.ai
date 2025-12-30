<?php

namespace App\Services;

use App\Exceptions\GroqServiceException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final readonly class GroqService
{
    public function __construct(
        private Factory $http,
    ) {}

    public function parseTicket(string $imageUrl): array
    {
        $response = $this->makeRequest($imageUrl);

        $this->validateResponse($response);

        $content = $this->extractContent($response);

        return $this->parseJsonContent($content);
    }

    private function makeRequest(string $imageUrl): array
    {
        $response = $this->http->withToken($this->apiKey())
            ->timeout($this->timeout())
            ->retry($this->maxRetries(), 1000)
            ->post($this->endpoint(), $this->buildPayload($imageUrl));

        if ($response->failed()) {
            Log::error('Groq API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw GroqServiceException::connectionFailed(
                $response->status(),
                $response->body()
            );
        }

        return $response->json();
    }

    private function validateResponse(array $response): void
    {
        if (! isset($response['choices'][0]['message']['content'])) {
            Log::error('Groq API Invalid Response', ['response' => $response]);

            throw GroqServiceException::emptyResponse();
        }
    }

    private function extractContent(array $response): string
    {
        return $response['choices'][0]['message']['content'];
    }

    private function parseJsonContent(string $content): array
    {
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON Parse Error', ['content' => $content]);

            throw GroqServiceException::invalidJson($content);
        }

        if (isset($data['products'])) {
            foreach ($data['products'] as &$product) {
                $product['id'] = (string) Str::uuid();
                $product['users'] = [];
            }
        }

        return $data;
    }

    private function buildPayload(string $imageUrl): array
    {
        return [
            'model' => config('groq.model'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->systemPrompt(),
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Por favor, procesa esta imagen y extrae los datos.',
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $imageUrl,
                            ],
                        ],
                    ],
                ],
            ],
            'temperature' => config('groq.temperature'),
            'stream' => false,
            'response_format' => ['type' => 'json_object'],
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'EOT'
            1. **ANÁLISIS DE IMAGEN/TEXTO:**
            - Si recibes una imagen, extrae todo el texto visible.
            - Si recibes texto, procésalo directamente.

            2. **VALIDACIÓN DE SEGURIDAD:**
            - Verifica que el documento sea un TICKET DE COMPRA o FACTURA.
            - Si NO es un ticket (es una foto de una persona, un paisaje, o texto sin sentido financiero), responde ÚNICAMENTE con el JSON de error `ERR_NO_TICKET`.

            3. **LÓGICA CRÍTICA DE EXTRACCIÓN (¡IMPORTANTE!):**
            - **Nombre del Establecimiento (`nombre`):** Busca el nombre comercial o marca (ej: "Burger King") en la cabecera. Evita la razón social legal (ej: "Alimentos del Norte S.L.") a menos que no haya otra opción.
            - **Total del Ticket (`total`):** Busca el importe final pagado (Suma total / Total a pagar).
            - **Productos (`products`):**
                - **PRECIO UNITARIO (`price`):** Esta es la prioridad máxima. Debes extraer el precio de UNA sola unidad.
                - *Caso A:* Si el ticket dice "2 x 1.50 = 3.00", el `price` es **1.50**.
                - *Caso B:* Si el ticket solo muestra el total de la línea (ej: "3 Latas ... 6.00"), calcula el unitario (6.00 / 3 = 2.00).
                - *Caso C:* Si hay descuentos aplicados en línea, intenta reflejar el precio final pagado por unidad si es posible, si no, usa el precio base.
                - **CANTIDAD (`quantity`):** Busca multiplicadores (ej: "2x", "2 Ud"). Si no aparece, asume 1.
                - **NOMBRE (`name`):** Limpia el texto.
                - *Mal:* "ART. 4552 COCA ZER 33CL"
                - *Bien:* "Coca Cola Zero 33cl"
                - Si el nombre es un código ininteligible, deduce el producto basado en el contexto si es obvio, o déjalo legible.

            4. **SALIDA:**
            - Devuelve SIEMPRE un único bloque de código JSON válido.
            - No añadidas explicaciones ni texto fuera del JSON.
            ━━━━━━━━━━
            ESTRUCTURA DEL JSON (ESPECIFICACIONES)
            ━━━━━━━━━━
            {
            "nombre": "String (Nombre del comercio)",
            "total": Float (Total del ticket, con 2 decimales),
            "products": [
                {
                    "name": "String (Nombre limpio del producto)",
                    "price": Float (PRECIO UNITARIO por producto, 2 decimales)",
                    "quantity": Integer (Número de unidades, defecto: 1),
                }
            ]
            }

            ━━━━━━━━━━
            EJEMPLO DE RAZONAMIENTO DE PRECIOS
            ━━━━━━━━━━
            Input: "2  CERVEZA RUBIA   a  1.20   Importe: 2.40"
            Output JSON: { "name": "Cerveza Rubia", "price": 1.20, "quantity": 2 }
            (Nota: Se extrajo 1.20, NO 2.40)

            ━━━━━━━━━━
            EJEMPLO DE RESPUESTA 
            ━━━━━━━━━━
            {
                "nombre": "Lidl",
                "total": 29.85,
                "products": [
                    {
                        "name": "Toallitas bebé",
                        "price": 0.89,
                        "quantity": 1
                    },
                    {
                        "name": "Té verde jazmín",
                        "price": 0.99,
                        "quantity": 1
                    },
                    {
                        "name": "Conejo entero",
                        "price": 7.54,
                        "quantity": 1
                    },
                    {
                        "name": "Rúcula",
                        "price": 0.75,
                        "quantity": 1
                    },
                    {
                        "name": "Turrones/mazapanes",
                        "price": 5.39,
                        "quantity": 1
                    },
                    {
                        "name": "Papel higiénico",
                        "price": 3.59,
                        "quantity": 1
                    },
                    {
                        "name": "Donación",
                        "price": 5.00,
                        "quantity": 1
                    }
                ]
            }
                
            ━━━━━━━━━━
            MANEJO DE ERRORES
            ━━━━━━━━━━
            Si la entrada no es válida, responde solo:
            {
                "error": {
                    "code": "ERR_NO_TICKET",
                    "message": "La imagen o texto proporcionado no parece ser un ticket de compra válido."
                }
            }
        
        '
        EOT;
    }

    private function apiKey(): string
    {
        $apiKey = config('groq.api_key');

        if (empty($apiKey)) {
            throw GroqServiceException::invalidApiKey();
        }

        return $apiKey;
    }

    private function endpoint(): string
    {
        return config('groq.base_url').'/chat/completions';
    }

    private function timeout(): int
    {
        return config('groq.timeout');
    }

    private function maxRetries(): int
    {
        return config('groq.max_retries');
    }
}
