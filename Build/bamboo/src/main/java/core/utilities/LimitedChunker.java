package core.utilities;

import java.util.*;
import java.util.concurrent.atomic.AtomicInteger;
import java.util.stream.Collectors;

/**
 * Chunk two lists into chunks of a given size
 *   where only `numLimitedJobsPerChunk` elements from one list are in each chunk
 *
 * @param <T>
 */
public class LimitedChunker<T> {

    private final int numLimitedElementsPerChunk;
    private final int totalChunkSize;

    public LimitedChunker(int numLimitedElementsPerChunk, int totalChunkSize) {
        this.numLimitedElementsPerChunk = numLimitedElementsPerChunk;
        this.totalChunkSize = totalChunkSize;

        if (numLimitedElementsPerChunk < 1) {
            throw new IllegalArgumentException("Number of limited Elements per Chunk must be greater than one");
        }
        if (totalChunkSize < 1) {
            throw new IllegalArgumentException("Total chunk size must be greater than one");
        }
        if (totalChunkSize < numLimitedElementsPerChunk) {
            throw new IllegalArgumentException("Number of limited elements must not be greater than total chunk size");
        }
    }

    public List<List<T>> chunk(List<T> limitedElements, List<T> normalElements) {
        // chunk the limited elements in sets of limitedChunkSize
        LinkedList<List<T>> chunks = new LinkedList<>(prepareChunks(limitedElements, numLimitedElementsPerChunk));

        // fill up these chunks with normal elements
        Iterator<T> normalJobIterator = normalElements.iterator();
        for (List<T> chunk : chunks) {
            fillChunkToSize(chunk, totalChunkSize, normalJobIterator);
        }
        // add chunks for all remaining normal elements, if any
        while (normalJobIterator.hasNext()) {
            List<T> chunk = new ArrayList<>(totalChunkSize);
            fillChunkToSize(chunk, totalChunkSize, normalJobIterator);
            chunks.add(chunk);
        }

        return chunks;
    }

    private void fillChunkToSize(List<T> chunk, int totalChunkSize, Iterator<T> remainingElementsIterator) {
        while (remainingElementsIterator.hasNext() && chunk.size() < totalChunkSize) {
            chunk.add(remainingElementsIterator.next());
        }
    }

    private Collection<List<T>> prepareChunks(List<T> inputList, int chunkSize) {
        AtomicInteger counter = new AtomicInteger();
        return inputList.stream().collect(Collectors.groupingBy(it -> counter.getAndIncrement() / chunkSize)).values();
    }

}
