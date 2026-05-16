<?php

namespace App\controllers\Api\admin;

use App\Services\Logger;
use models\Allocation;
use models\Node;
use models\Server;
use Vatts\Router\Request;
use Vatts\Router\Response;

class NodesController
{
    /**
     * Retorna apenas os nodes online, opcionalmente filtrando por localização.
     * Ideal para auto-deploy ou para listar apenas nodes disponíveis no momento da criação de um servidor.
     */
    public function getOnlineNodesByLocation(Request $request, Response $response): Response
    {
        // Se a localização vier por query string (ex: ?location=BR)
        $location = $request->getQuery()['location'] ?? '';

        try {
            if ($location !== '') {
                $nodes = Node::where('location', $location);
            } else {
                // Se não passar localização, busca todos
                $nodes = Node::all();
            }

            $onlineNodes = [];
            foreach ($nodes as $node) {
                try {

                    $isOnline = $node->getStatus();
                    if ($isOnline) {
                        unset($node->view_map);
                        $array = $node->toArray();
                        // adiciona a quantidade de svs com essa node
                        $serversCount = Server::witch('nodeUuid', $node->id)->count();
                        $array['serversCount'] = $serversCount;
                        $onlineNodes[] = $array;
                    }
                } catch (\Throwable $e) {
                    Logger::error($e);
                    // Se estourar exceção, o node não está comunicando (offline), pulamos ele.
                    continue;
                }
            }

            return $response->json([
                'success' => true,
                'data'    => $onlineNodes
            ])->status(200);

        } catch (\Exception $e) {
            return $response->json([
                'success' => false,
                'error'   => 'Erro ao buscar nodes online: ' . $e->getMessage()
            ])->status(500);
        }
    }

    /**
     * Retorna apenas as alocações (portas) livres para um node específico.
     */
    public function getFreeAllocationsByNode(Request $request, Response $response): Response
    {
        $nodeId = $request->getParam('nodeId');
        if (!$nodeId) {
            return $response->json([
                'success' => false,
                'error'   => 'ID do node não fornecido na rota.'
            ])->status(400);
        }

        try {
            $allocations = Allocation::where('nodeId', $nodeId);

            $freeAllocations = [];
            foreach ($allocations as $alloc) {
                // Considera livre se assignedTo for nulo ou string vazia
                if (empty($alloc->assignedTo)) {
                    unset($alloc->view_map);
                    $freeAllocations[] = $alloc;
                }
            }

            return $response->json([
                'success' => true,
                'data'    => $freeAllocations
            ], 200);

        } catch (\Exception $e) {
            return $response->json([
                'success' => false,
                'error'   => 'Erro ao buscar alocações livres: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifica o status de comunicação de um node específico.
     */
    public function getNodeStatus(Request $request, Response $response): Response
    {
        $nodeId = $request->getParam('nodeId');

        if (!$nodeId) {
            return $response->json([
                'success' => false,
                'error'   => 'ID do node não fornecido.'
            ], 400);
        }

        $node = Node::find($nodeId);
        if (!$node) {
            return $response->json([
                'success' => false,
                'error'   => 'Node não encontrado.'
            ], 404);
        }

        unset($node->view_map);

        try {
            $node->online = (bool) $node->status();

            return $response->json([
                'success' => true,
                'data'    => $node
            ], 200);

        } catch (\Throwable $e) {
            // Em caso de erro na comunicação, assumimos offline
            $node->online = false;
            $node->reason = 'Falha de comunicação com o Node.';

            return $response->json([
                'success' => true,
                'data'    => $node
            ], 200);
        }
    }
}