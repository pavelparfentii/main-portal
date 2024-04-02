import Web3 from "web3";
import fetch from 'node-fetch';
import contractABI from '../node/DAContractABI/contractABI.js';

const contractAddress = "0x25593A50255Bfb30eA027f6966417b0BF780401d";
const totalTokens = 8888; // Assuming this is the total number of tokens

async function fetchApiKey() {
    const response = await fetch('https://safesoul.test-dev.site/api/airdrop/infuraKey');
    const data = await response.json();
    return data.key;
}

async function getWeb3WithApiKey(apiKey) {
    return new Web3(new Web3.providers.HttpProvider(`https://mainnet.infura.io/v3/${apiKey}`));
}

async function initializeContract(web3Instance) {
    return new web3Instance.eth.Contract(contractABI, contractAddress);
}

async function checkOriginalMinterAndNeverSold(web3Instance, contract) {
    for (let tokenId = 1; tokenId <= totalTokens; tokenId++) {
        const originalMinter = await contract.methods.originalMinter(tokenId).call();

        const events = await contract.getPastEvents('Transfer', {
            filter: { tokenId: tokenId },
            fromBlock: 0,
            toBlock: 'latest'
        });

        const hasSold = events.some(event => event.returnValues.from.toLowerCase() === originalMinter.toLowerCase());

        if (!hasSold) {
            //console.log(`Token ID ${tokenId} minted by ${originalMinter} has never been sold.`);

            const output = {
                tokenId: tokenId,
                wallet: originalMinter.toLowerCase(),
                message: `Token ID ${tokenId} minted by ${originalMinter} has never been sold.`
            }
            console.log(JSON.stringify(output));
        }
    }
}

async function main() {
    try {
        const apiKey = await fetchApiKey();
        const web3Instance = await getWeb3WithApiKey(apiKey);
        const contract = await initializeContract(web3Instance);

        await checkOriginalMinterAndNeverSold(web3Instance, contract);
    } catch (error) {
        console.error('Error:', error);
    }
}

main();
