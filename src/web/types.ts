export interface ServerData {
    id: number;
    name: string;
    ownerId: string;
    ram: number;
    cpu: number;
    disk: number;
    nodeUuid: string;
    serverUuid: string;
    created_at: string;
    updated_at: string;
    dockerImage: string;
    coreId?: string;
    description?: string;
    startupCommand?: string;
    stopCommand?: string;
    envVars?: string;
    additionalAllocations?: string;
    maxAdditionalAllocations?: number;
    allocationId?: number;
    group?: any;
    suspended?: number;
    allocation?: {
        id: number;
        nodeId: string;
        ip: string;
        externalIp: string;
        port: number;
        assignedTo: string;
    };
}