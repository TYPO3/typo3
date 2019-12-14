package utilities;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import core.utilities.LimitedChunker;
import org.junit.Test;
import org.junit.runner.RunWith;
import org.junit.runners.Parameterized;

import java.util.ArrayList;
import java.util.Arrays;
import java.util.Collection;
import java.util.List;

@RunWith(Parameterized.class)
public class LimitedChunkerTest {
    private final int numLimitedJobs;
    private final int numNormalJobs;
    private final int limitedChunkSize;
    private final int totalChunkSize;
    private int expectedNumberOfChunks;

    @Test(expected = IllegalArgumentException.class)
    public void throwsOnZeroLimitedElementsPerChunk() {
        LimitedChunker<JobFixture> chunker = new LimitedChunker<>(0, 25);
    }

    @Test(expected = IllegalArgumentException.class)
    public void throwsOnZeroTotalChunkSize() {
        LimitedChunker<JobFixture> chunker = new LimitedChunker<>(25, 0);
    }

    @Test(expected = IllegalArgumentException.class)
    public void throwsOnMoreLimitedThanChunkSize() {
        LimitedChunker<JobFixture> chunker = new LimitedChunker<>(25, 12);
    }

    @Test
    public void returnsEmptyChunkListWithEmptyInput() {
        LimitedChunker<JobFixture> chunker = new LimitedChunker<>(12, 25);

        ArrayList<JobFixture> jobs = new ArrayList<>(0);
        ArrayList<JobFixture> limitedJobs = new ArrayList<>(0);

        List<List<JobFixture>> chunks = chunker.chunk(limitedJobs, jobs);

        assert chunks.size() == 0;
    }

    @Parameterized.Parameters
    public static Collection<Object[]> data() {
        return Arrays.asList(
            new Integer[]{25, 25, 25, 50, 1},
            new Integer[]{25, 0, 25, 25, 1},
            new Integer[]{100, 100, 25, 50, 4},
            new Integer[]{110, 100, 25, 50, 5},
            new Integer[]{100, 110, 25, 50, 5}
        );
    }

    public LimitedChunkerTest(int numLimitedJobs, int numNormalJobs, int limitedChunkSize, int totalChunkSize, int expectedNumberOfChunks) {
        this.numLimitedJobs = numLimitedJobs;
        this.numNormalJobs = numNormalJobs;
        this.limitedChunkSize = limitedChunkSize;
        this.totalChunkSize = totalChunkSize;
        this.expectedNumberOfChunks = expectedNumberOfChunks;
    }

    @Test
    public void chunksCorrectly() {
        ArrayList<JobFixture> limitedJobs = getJobs(numLimitedJobs, true);
        ArrayList<JobFixture> normalJobs = getJobs(numNormalJobs, false);

        List<List<JobFixture>> chunks = new LimitedChunker<JobFixture>(limitedChunkSize, totalChunkSize).chunk(limitedJobs, normalJobs);

        assert chunks.size() == expectedNumberOfChunks;

        int actualNumberOfJobs = chunks.stream().map(List::size).reduce(0, Integer::sum);
        assert actualNumberOfJobs == numLimitedJobs + numNormalJobs;


        int chunkIndex = 0;
        for (List<JobFixture> chunk : chunks) {
            long numLimited = chunk.stream().filter(JobFixture::isLimited).count();
            assert(numLimited <= limitedChunkSize);
            if (chunkIndex < chunks.size() - 1) {
                // all chunks must be full
                assert(chunk.size() == totalChunkSize);
            } else {
                //except the last one
                assert(chunk.size() <= totalChunkSize);
            }
            chunkIndex++;
        }
    }

    private ArrayList<JobFixture> getJobs(int numLimitedJobs, boolean isLimited) {
        ArrayList<JobFixture> jobs = new ArrayList<>(numLimitedJobs);
        for (int i = 0; i < numLimitedJobs; i++) {
            jobs.add(new JobFixture(isLimited));
        }
        return jobs;
    }
}
