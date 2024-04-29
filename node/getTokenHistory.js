import fetch from 'node-fetch';
import dotenv from 'dotenv';
import { fileURLToPath } from 'url';
import path from 'path';

// Correct way to get __dirname in ES module
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const { API_ENDPOINT} = dotenv
    .config({ path: __dirname + '/../.env' }).parsed;
// Setup your variables
// const API_KEY = 'X4C4QT9R9VKIWRNFIDWZ3FWJ2C6RVD7XYC';
const CONTRACT_ADDRESS = '0x25593A50255Bfb30eA027f6966417b0BF780401d'; // NFT contract address
const TOKEN_ID = process.argv[2];
const ADDRESS = process.argv[3];
// const TOKEN_ID = '6808'; // The specific token ID you're interested in
// const ADDRESS = '0x8857e2C0602bDfd58aD6A47bab5438d62c698eC6'; // The wallet address you're checking

async function fetchApiKey() {
    const response = await fetch(API_ENDPOINT+'etherscanApiKey');
    const data = await response.json(); // Assuming the key is returned in JSON format
    // console.log(data);
    return data.key; // Adjust according to your actual API response structure
}

// Function to get transfer events from Etherscan API
const getTransferEvents = async () => {
    const API_KEY = await fetchApiKey();
    const url = `https://api.etherscan.io/api?module=account&action=tokennfttx&contractaddress=${CONTRACT_ADDRESS}&address=${ADDRESS}&page=1&offset=1000&sort=asc&apikey=${API_KEY}`;

    try {
        const response = await fetch(url);
        const data = await response.json();

        if (data.status !== '1') {
            console.error('Failed to fetch data:', data.result);
            return;
        }

        // Filter for events related to the specific TOKEN_ID
        const relatedTransfers = data.result.filter(transfer => transfer.tokenID === TOKEN_ID);

        // Output the filtered transfer events
        //console.log(relatedTransfers);
        return relatedTransfers;
    } catch (error) {
        console.error('Error fetching transfer events:', error);
    }
};

// Function to analyze the transfer events to determine if the address has owned the NFT for at least a year
const hasOwnedForAYear = (transfers) => {
    const oneYearAgo = new Date();
    oneYearAgo.setFullYear(oneYearAgo.getFullYear() - 1);

    // Assuming transfers are sorted ascending by timeStamp
    const lastTransfer = transfers[transfers.length - 1];
    if (!lastTransfer) {
        const errorMessage = {
            state: 'error',
            data: 'No transfer events found.',
        };
        console.log(JSON.stringify(errorMessage));
        return false;
    }

    const lastTransferDate = new Date(lastTransfer.timeStamp * 1000);

    // Check if the last transfer to the address was at least a year ago
    if (lastTransferDate <= oneYearAgo && lastTransfer.to.toLowerCase() === ADDRESS.toLowerCase()) {
        // console.log('The address has owned the NFT for at least a year.');
        return true;
    } else {
        const errorMessage = {
            state: 'error',
            data: 'The address has not owned the NFT for at least a year.',
        };
        console.log(JSON.stringify(errorMessage));
        // console.log('The address has not owned the NFT for at least a year.');
        return false;
    }
};

// Main function to execute the script
const main = async () => {
    const transfers = await getTransferEvents();
    if (transfers && transfers.length > 0) {
        // hasOwnedForAYear(transfers);
        wasOwnedForAtLeastAYear(transfers)
    } else {
        const errorMessage = {
            state: 'error',
            data: 'Unable to retrieve transfer history or no transfers related to the token ID and address.',
        };
        console.log(JSON.stringify(errorMessage));
        // console.log('Unable to retrieve transfer history or no transfers related to the token ID and address.');
    }
};

main();


// Function to determine if the token was owned by the wallet for at least a year
const wasOwnedForAtLeastAYear = (transfers) => {
    // Sort transfers by timeStamp in ascending order
    transfers.sort((a, b) => a.timeStamp - b.timeStamp);

    for (let i = 0; i < transfers.length; i++) {
        const transfer = transfers[i];
        // Check if the token was transferred to the specified address
        if (transfer.to.toLowerCase() === ADDRESS.toLowerCase()) {
            const ownershipStartDate = new Date(transfer.timeStamp * 1000);
            let ownershipEndDate = new Date(); // Assume current date unless found otherwise

            // Check if there's a subsequent transfer away from the address
            if (i + 1 < transfers.length && transfers[i + 1].from.toLowerCase() === ADDRESS.toLowerCase()) {
                ownershipEndDate = new Date(transfers[i + 1].timeStamp * 1000);
            }

            // Calculate the duration of ownership
            const ownedFor = ownershipEndDate - ownershipStartDate;
            const oneYearInMilliseconds = 365 * 24 * 60 * 60 * 1000;

            if (ownedFor >= oneYearInMilliseconds) {
                const success = {
                    state: 'success',
                    data: 'The token '+ TOKEN_ID + ' was owned by the address for at least a year during the period:' + ownershipStartDate + ' to ' + ownershipEndDate,
                };
                console.log(JSON.stringify(success));
                return true;
            }
        }
    }
    const errorMessage = {
        state: 'error',
        data: 'The token was not owned by the address for at least a year at any uninterrupted period.',
    };
    console.log(JSON.stringify(errorMessage));
    // console.log('The token was not owned by the address for at least a year at any uninterrupted period.');
    return false;
};
